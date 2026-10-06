<?php

namespace Tests\Feature;

use App\Models\Plato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuPublicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_carta_muestra_los_platos_en_orden_alfabetico_con_formato_de_moneda(): void
    {
        Plato::factory()->create(['nombre' => 'Tiramisú', 'precio' => 15900]);
        Plato::factory()->create(['nombre' => 'Ajiaco Santafereño', 'precio' => 32000]);
        Plato::factory()->create(['nombre' => 'Hamburguesa Artesanal', 'precio' => 28500.5]);

        $this->get('/menu')
            ->assertOk()
            ->assertSeeInOrder(['Ajiaco Santafereño', 'Hamburguesa Artesanal', 'Tiramisú'])
            ->assertSee('$ 32.000')
            ->assertSee('$ 28.500,50')
            ->assertSee('$ 15.900')
            ->assertSee('Ver detalle');
    }

    public function test_la_carta_solo_muestra_nombre_y_precio_con_enlace_al_detalle(): void
    {
        $plato = Plato::factory()->create(['descripcion' => 'Este texto solo aparece en el detalle del plato.']);

        $this->get('/menu')
            ->assertOk()
            ->assertSee($plato->nombre)
            ->assertSee($plato->precio_formateado)
            ->assertSee(route('menu.show', $plato))
            ->assertDontSee('Este texto solo aparece en el detalle del plato.')
            ->assertDontSee($plato->imagen);
    }

    public function test_la_carta_no_muestra_platos_eliminados(): void
    {
        Plato::factory()->create(['nombre' => 'Arepa con Queso']);
        Plato::factory()->create(['nombre' => 'Bandeja Paisa'])->delete();

        $this->get('/menu')
            ->assertOk()
            ->assertSee('Arepa con Queso')
            ->assertDontSee('Bandeja Paisa');
    }

    public function test_la_carta_vacia_muestra_un_mensaje(): void
    {
        $this->get('/menu')
            ->assertOk()
            ->assertSee('La carta está vacía');
    }

    public function test_el_detalle_muestra_nombre_imagen_descripcion_y_precio(): void
    {
        $plato = Plato::factory()->create([
            'nombre' => 'Salmón a la Parrilla',
            'precio' => 46500,
            'descripcion' => 'Filete de salmón con espárragos y puré de papa criolla.',
        ]);

        $this->get("/menu/{$plato->id}")
            ->assertOk()
            ->assertSee('Salmón a la Parrilla')
            ->assertSee($plato->imagen_url)
            ->assertSee('Filete de salmón con espárragos y puré de papa criolla.')
            ->assertSee('$ 46.500')
            ->assertSee(route('menu.index'))
            ->assertSee('Volver al menú');
    }

    public function test_el_detalle_de_un_plato_eliminado_o_inexistente_responde_404(): void
    {
        $plato = Plato::factory()->create();
        $plato->delete();

        $this->get("/menu/{$plato->id}")
            ->assertNotFound()
            ->assertSee('Este plato no está en la carta');

        $this->get('/menu/9999')->assertNotFound();
    }
}
