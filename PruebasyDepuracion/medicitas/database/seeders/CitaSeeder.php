<?php

namespace Database\Seeders;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\User;
use Illuminate\Database\Seeder;

class CitaSeeder extends Seeder
{
    /**
     * 24 citas entre los pacientes y los médicos ya creados, con los tres estados.
     */
    public function run(): void
    {
        $pacientes = User::pacientes()->get();
        $medicos = User::medicos()->get();

        Cita::factory()
            ->count(24)
            ->state(function () use ($pacientes, $medicos) {
                $medico = $medicos->random();

                return [
                    'patient_id' => $pacientes->random()->id,
                    'doctor_id' => $medico->id,
                    'especialidad' => $medico->especialidad,
                ];
            })
            ->sequence(
                ['estado' => EstadoCita::Confirmada],
                ['estado' => EstadoCita::Pendiente],
                ['estado' => EstadoCita::Confirmada],
                ['estado' => EstadoCita::Cancelada],
            )
            ->create();
    }
}
