<?php

namespace Tests\Feature;

use App\Models\Plato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlatoAdministracionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * @return array<string, mixed>
     */
    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Hamburguesa Artesanal',
            'precio' => '28500.00',
            'descripcion' => 'Carne madurada, queso cheddar y pan brioche.',
            'imagen' => UploadedFile::fake()->image('foto.png', 800, 600),
        ], $cambios);
    }

    public function test_el_formulario_de_creacion_permite_subir_archivos(): void
    {
        $this->get('/platos/crear')
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="nombre"', false)
            ->assertSee('name="precio"', false)
            ->assertSee('name="descripcion"', false)
            ->assertSee('name="imagen"', false);
    }

    public function test_registra_un_plato_y_guarda_la_imagen_en_la_carpeta_del_anio_y_mes(): void
    {
        $this->travelTo(Carbon::create(2026, 9, 15, 10));

        $this->post('/platos', $this->datosValidos())
            ->assertRedirect('/platos/crear')
            ->assertSessionHas('success', 'El plato «Hamburguesa Artesanal» se registró correctamente.');

        $plato = Plato::sole();
        $this->assertSame('Hamburguesa Artesanal', $plato->nombre);
        $this->assertSame('28500.00', $plato->precio);
        $this->assertSame('Carne madurada, queso cheddar y pan brioche.', $plato->descripcion);
        $this->assertSame('platos/2026/09/hamburguesa-artesanal.png', $plato->imagen);
        Storage::disk('public')->assertExists('platos/2026/09/hamburguesa-artesanal.png');
    }

    public function test_no_sobrescribe_una_imagen_existente_con_el_mismo_nombre(): void
    {
        $carpeta = 'platos/'.now()->format('Y/m');
        Storage::disk('public')->put("{$carpeta}/cafe.png", 'imagen de otro plato');

        $this->post('/platos', $this->datosValidos(['nombre' => 'Café']))->assertSessionHasNoErrors();

        $this->assertSame("{$carpeta}/cafe-2.png", Plato::sole()->imagen);
        Storage::disk('public')->assertExists("{$carpeta}/cafe.png");
    }

    public function test_valida_los_campos_obligatorios(): void
    {
        $this->post('/platos', [])
            ->assertSessionHasErrors([
                'nombre' => 'El campo nombre es obligatorio.',
                'precio' => 'El campo precio es obligatorio.',
                'descripcion' => 'El campo descripción es obligatorio.',
                'imagen' => 'Debes seleccionar una imagen para el plato.',
            ]);

        $this->assertDatabaseCount('platos', 0);
    }

    #[DataProvider('datosInvalidos')]
    public function test_rechaza_datos_invalidos(string $campo, mixed $valor): void
    {
        $this->post('/platos', $this->datosValidos([$campo => $valor]))
            ->assertSessionHasErrors($campo);

        $this->assertDatabaseCount('platos', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function datosInvalidos(): array
    {
        return [
            'nombre de menos de 3 caracteres' => ['nombre', 'Té'],
            'nombre de más de 100 caracteres' => ['nombre', str_repeat('a', 101)],
            'precio no numérico' => ['precio', 'veinte mil'],
            'precio menor a 1000' => ['precio', '999.99'],
            'precio con más de 2 decimales' => ['precio', '28500.555'],
            'descripción de menos de 10 caracteres' => ['descripcion', 'Muy rica'],
            'imagen en formato no permitido (gif)' => ['imagen', UploadedFile::fake()->image('foto.gif')],
            'archivo que no es una imagen' => ['imagen', UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf')],
            'imagen de más de 2 MB' => ['imagen', UploadedFile::fake()->image('grande.jpg')->size(2049)],
        ];
    }

    public function test_conserva_lo_escrito_y_muestra_el_error_cuando_falla_la_validacion(): void
    {
        $this->from('/platos/crear')
            ->post('/platos', $this->datosValidos(['precio' => '500']))
            ->assertRedirect('/platos/crear')
            ->assertSessionHasErrors(['precio' => 'El precio mínimo es de $ 1.000.'])
            ->assertSessionHasInput('nombre', 'Hamburguesa Artesanal');

        $this->followingRedirects()
            ->from('/platos/crear')
            ->post('/platos', $this->datosValidos(['precio' => '500']))
            ->assertSee('value="Hamburguesa Artesanal"', false)
            ->assertSee('El precio mínimo es de $ 1.000.');
    }

    public function test_el_nombre_debe_ser_unico(): void
    {
        Plato::factory()->create(['nombre' => 'Ajiaco Santafereño']);

        $this->post('/platos', $this->datosValidos(['nombre' => 'Ajiaco Santafereño']))
            ->assertSessionHasErrors(['nombre' => 'Ya existe un plato registrado con este nombre.']);
    }

    public function test_el_nombre_de_un_plato_eliminado_se_puede_reutilizar(): void
    {
        Plato::factory()->create(['nombre' => 'Ajiaco Santafereño'])->delete();

        $this->post('/platos', $this->datosValidos(['nombre' => 'Ajiaco Santafereño']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Plato::count());
    }

    public function test_el_listado_muestra_los_platos_con_sus_acciones(): void
    {
        $plato = Plato::factory()->create(['nombre' => 'Ensalada César', 'precio' => 24000]);

        $this->get('/platos')
            ->assertOk()
            ->assertSee('#'.$plato->id)
            ->assertSee($plato->imagen_url)
            ->assertSee('Ensalada César')
            ->assertSee('$ 24.000')
            ->assertSee(route('platos.edit', $plato))
            ->assertSee(route('platos.destroy', $plato))
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('data-confirm=', false)
            ->assertSee(route('platos.create'));
    }

    public function test_el_listado_sin_platos_invita_a_registrar_uno(): void
    {
        $this->get('/platos')
            ->assertOk()
            ->assertSee('Aún no hay platos registrados')
            ->assertSee(route('platos.create'));
    }

    public function test_el_formulario_de_edicion_carga_los_datos_actuales(): void
    {
        $plato = Plato::factory()->create([
            'nombre' => 'Pizza Margarita',
            'precio' => 36000,
            'descripcion' => 'Masa madre, tomate y albahaca fresca.',
        ]);

        $this->get("/platos/{$plato->id}/editar")
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="Pizza Margarita"', false)
            ->assertSee('value="36000.00"', false)
            ->assertSee('Masa madre, tomate y albahaca fresca.')
            ->assertSee($plato->imagen_url);
    }

    public function test_actualizar_sin_imagen_nueva_conserva_la_anterior(): void
    {
        Storage::disk('public')->put('platos/2026/09/pizza.png', 'imagen');
        $plato = Plato::factory()->create(['nombre' => 'Pizza', 'imagen' => 'platos/2026/09/pizza.png']);

        $this->put("/platos/{$plato->id}", [
            'nombre' => 'Pizza Margarita',
            'precio' => '36000',
            'descripcion' => 'Masa madre, tomate y albahaca fresca.',
        ])
            ->assertRedirect('/platos')
            ->assertSessionHas('success', 'El plato «Pizza Margarita» se actualizó correctamente.');

        $plato->refresh();
        $this->assertSame('Pizza Margarita', $plato->nombre);
        $this->assertSame('36000.00', $plato->precio);
        $this->assertSame('platos/2026/09/pizza.png', $plato->imagen);
        Storage::disk('public')->assertExists('platos/2026/09/pizza.png');
    }

    public function test_actualizar_con_imagen_nueva_la_guarda_en_anio_mes_y_borra_la_anterior(): void
    {
        Storage::disk('public')->put('platos/2026/09/pizza.png', 'imagen vieja');
        $plato = Plato::factory()->create(['nombre' => 'Pizza', 'imagen' => 'platos/2026/09/pizza.png']);
        $this->travelTo(Carbon::create(2026, 10, 3, 12));

        $this->put("/platos/{$plato->id}", [
            'nombre' => 'Pizza',
            'precio' => '36000',
            'descripcion' => 'Masa madre, tomate y albahaca fresca.',
            'imagen' => UploadedFile::fake()->image('nueva.webp'),
        ])->assertRedirect('/platos');

        $this->assertSame('platos/2026/10/pizza.webp', $plato->fresh()->imagen);
        Storage::disk('public')->assertExists('platos/2026/10/pizza.webp');
        Storage::disk('public')->assertMissing('platos/2026/09/pizza.png');
    }

    public function test_al_editar_puede_conservar_su_propio_nombre(): void
    {
        $plato = Plato::factory()->create(['nombre' => 'Tiramisú']);

        $this->put("/platos/{$plato->id}", [
            'nombre' => 'Tiramisú',
            'precio' => '15900',
            'descripcion' => 'Postre italiano con café y cacao.',
        ])->assertSessionHasNoErrors();
    }

    public function test_al_editar_no_puede_usar_el_nombre_de_otro_plato(): void
    {
        Plato::factory()->create(['nombre' => 'Tiramisú']);
        $plato = Plato::factory()->create(['nombre' => 'Flan de Caramelo']);

        $this->put("/platos/{$plato->id}", [
            'nombre' => 'Tiramisú',
            'precio' => '15900',
            'descripcion' => 'Postre con caramelo casero.',
        ])->assertSessionHasErrors(['nombre' => 'Ya existe un plato registrado con este nombre.']);

        $this->assertSame('Flan de Caramelo', $plato->fresh()->nombre);
    }

    public function test_eliminar_hace_soft_delete_y_conserva_la_imagen(): void
    {
        Storage::disk('public')->put('platos/2026/09/flan.png', 'imagen');
        $plato = Plato::factory()->create(['nombre' => 'Flan de Caramelo', 'imagen' => 'platos/2026/09/flan.png']);

        $this->delete("/platos/{$plato->id}")
            ->assertRedirect('/platos')
            ->assertSessionHas('success', 'El plato «Flan de Caramelo» se eliminó correctamente.');

        $this->assertSoftDeleted($plato);
        Storage::disk('public')->assertExists('platos/2026/09/flan.png');

        // El mensaje flash menciona el nombre, por eso se verifica que ya no esté su fila.
        $this->get('/platos')
            ->assertDontSee(route('platos.edit', $plato))
            ->assertSee('0 platos registrados');
    }

    public function test_la_imagen_se_sirve_desde_el_enlace_publico_de_storage(): void
    {
        $plato = Plato::factory()->create(['imagen' => 'platos/2026/09/flan.png']);

        $this->assertStringEndsWith('/storage/platos/2026/09/flan.png', $plato->imagen_url);
        $this->assertSame(storage_path('app/public'), config('filesystems.links')[public_path('storage')]);
    }
}
