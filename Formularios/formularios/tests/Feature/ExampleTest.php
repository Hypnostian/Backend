<?php

namespace Tests\Feature;

use App\Models\Plato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_la_portada_muestra_los_platos_mas_recientes(): void
    {
        Plato::factory()->create(['nombre' => 'Pizza Margarita']);
        $reciente = Plato::factory()->create(['nombre' => 'Hamburguesa Artesanal']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Hamburguesa Artesanal')
            ->assertSee(route('menu.show', $reciente))
            ->assertSee(route('platos.create'));
    }
}
