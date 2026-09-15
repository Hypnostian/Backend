<?php

namespace Database\Seeders;

use App\Models\Expansion;
use App\Models\Idioma;
use App\Models\Juego;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $idiomas = collect([
            ['nombre' => 'Español', 'codigo' => 'es'],
            ['nombre' => 'Inglés', 'codigo' => 'en'],
            ['nombre' => 'Francés', 'codigo' => 'fr'],
        ])->mapWithKeys(function (array $datos) {
            $idioma = Idioma::updateOrCreate(
                ['codigo' => $datos['codigo']],
                ['nombre' => $datos['nombre']],
            );

            return [$datos['codigo'] => $idioma];
        });

        $juegos = collect([
            ['titulo' => 'A la Mar', 'anio' => 2020, 'idioma' => 'es'],
            ['titulo' => 'Mars Attacks!', 'anio' => 2015, 'idioma' => 'en'],
            ['titulo' => 'La Comarca', 'anio' => 2018, 'idioma' => 'es'],
            ['titulo' => 'Catan', 'anio' => 1995, 'idioma' => 'en'],
            ['titulo' => 'Azul', 'anio' => 2017, 'idioma' => 'fr'],
            ['titulo' => 'Wingspan', 'anio' => 2019, 'idioma' => 'en'],
        ])->mapWithKeys(function (array $datos) use ($idiomas) {
            $juego = Juego::updateOrCreate(
                ['titulo' => $datos['titulo']],
                [
                    'anio' => $datos['anio'],
                    'idioma_id' => $idiomas[$datos['idioma']]->id,
                ],
            );

            return [$datos['titulo'] => $juego];
        });

        $expansiones = [
            ['juego' => 'A la Mar', 'titulo' => 'Mares del Sur', 'idioma' => 'es'],
            ['juego' => 'Mars Attacks!', 'titulo' => 'Mars Attacks: Invasion', 'idioma' => 'en'],
            ['juego' => 'La Comarca', 'titulo' => 'Tierras Lejanas', 'idioma' => 'es'],
            ['juego' => 'Catan', 'titulo' => 'Navegantes', 'idioma' => 'en'],
            ['juego' => 'Azul', 'titulo' => 'Pabellon de Verano', 'idioma' => 'fr'],
        ];

        foreach ($expansiones as $datos) {
            Expansion::updateOrCreate(
                [
                    'juego_id' => $juegos[$datos['juego']]->id,
                    'titulo' => $datos['titulo'],
                ],
                ['idioma_id' => $idiomas[$datos['idioma']]->id],
            );
        }
    }
}
