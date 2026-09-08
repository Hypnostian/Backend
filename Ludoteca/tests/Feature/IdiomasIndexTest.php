<?php

namespace Tests\Feature;

use App\Models\Idioma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdiomasIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_all_idiomas(): void
    {
        Idioma::create(['nombre' => 'Español', 'codigo' => 'es']);
        Idioma::create(['nombre' => 'Inglés', 'codigo' => 'en']);

        $response = $this->get('/idiomas');

        $response->assertOk();
        $response->assertSee('Español');
        $response->assertSee('Inglés');
    }
}
