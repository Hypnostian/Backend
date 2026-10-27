<?php

namespace Database\Factories;

use App\Enums\Especialidad;
use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state: un paciente.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Nombre y apellidos sin títulos: name() de es_ES a veces agrega "Dr." o "Lic."
            'name' => fake()->firstName().' '.fake()->lastName().' '.fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'rol' => Rol::Paciente,
            'especialidad' => null,
        ];
    }

    /**
     * Un médico de la especialidad indicada (o de una al azar).
     */
    public function medico(?Especialidad $especialidad = null): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => Rol::Medico,
            'especialidad' => $especialidad ?? fake()->randomElement(Especialidad::cases()),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
