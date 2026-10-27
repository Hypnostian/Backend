<?php

namespace Database\Factories;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cita>
 */
class CitaFactory extends Factory
{
    /**
     * Define the model's default state: una cita pendiente con su propio paciente y su propio médico.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => User::factory(),
            'doctor_id' => User::factory()->medico(),
            // La especialidad de la cita es la que atiende el médico asignado
            'especialidad' => fn (array $attributes) => User::find($attributes['doctor_id'])->especialidad,
            'fecha_hora' => now()
                ->addDays(fake()->numberBetween(1, 60))
                ->setTime(fake()->numberBetween(7, 17), fake()->randomElement([0, 30])),
            'estado' => EstadoCita::Pendiente,
            'motivo' => fake()->randomElement([
                'Chequeo general anual.',
                'Control de presión arterial.',
                'Dolor de cabeza frecuente desde hace dos semanas.',
                'Fiebre y tos persistente.',
                'Revisión de un lunar que cambió de color.',
                'Control de crecimiento y vacunas.',
                'Dolor en el pecho al hacer ejercicio.',
                'Erupción en la piel con picazón.',
                'Control prenatal del segundo trimestre.',
                'Lectura de resultados de laboratorio.',
            ]),
        ];
    }

    public function confirmada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoCita::Confirmada,
        ]);
    }

    public function cancelada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoCita::Cancelada,
        ]);
    }
}
