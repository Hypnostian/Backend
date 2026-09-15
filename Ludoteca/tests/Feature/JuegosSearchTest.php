<?php

namespace Tests\Feature;

use App\Models\Expansion;
use App\Models\Idioma;
use App\Models\Juego;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JuegosSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_partial_names_in_games_and_expansions(): void
    {
        $idioma = Idioma::create(['nombre' => 'Español', 'codigo' => 'es']);
        $juego = Juego::create(['titulo' => 'A la Mar', 'anio' => 2020, 'idioma_id' => $idioma->id]);
        Expansion::create([
            'titulo' => 'La Comarca',
            'juego_id' => $juego->id,
            'idioma_id' => $idioma->id,
        ]);

        $response = $this->get('/juegos/buscar?q=mar');

        $response->assertOk();
        $response->assertSee('A la Mar');
        $response->assertSee('La Comarca');
    }

    public function test_search_filters_by_year_range_and_language(): void
    {
        $espanol = Idioma::create(['nombre' => 'Español', 'codigo' => 'es']);
        $ingles = Idioma::create(['nombre' => 'Inglés', 'codigo' => 'en']);
        Juego::create(['titulo' => 'Juego antiguo', 'anio' => 2010, 'idioma_id' => $espanol->id]);
        Juego::create(['titulo' => 'Juego encontrado', 'anio' => 2020, 'idioma_id' => $ingles->id]);

        $response = $this->get('/juegos/buscar?anio_desde=2015&anio_hasta=2025&idioma_id=' . $ingles->id);

        $response->assertOk();
        $response->assertSee('Juego encontrado');
        $response->assertDontSee('Juego antiguo');
    }
}
