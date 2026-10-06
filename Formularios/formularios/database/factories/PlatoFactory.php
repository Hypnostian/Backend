<?php

namespace Database\Factories;

use App\Models\Plato;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plato>
 */
class PlatoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = Str::title(fake()->unique()->words(3, true));

        return [
            'nombre' => $nombre,
            'precio' => fake()->numberBetween(8, 60) * 1000,
            'descripcion' => fake()->paragraph(),
            'imagen' => 'platos/'.now()->format('Y/m').'/'.Str::slug($nombre).'.jpg',
        ];
    }
}
