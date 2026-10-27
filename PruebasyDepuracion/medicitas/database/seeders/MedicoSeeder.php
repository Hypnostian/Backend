<?php

namespace Database\Seeders;

use App\Enums\Especialidad;
use App\Models\User;
use Illuminate\Database\Seeder;

class MedicoSeeder extends Seeder
{
    /**
     * Médicos de la clínica (contraseña: password).
     */
    public function run(): void
    {
        $medicos = [
            ['Dra. Laura Méndez', 'laura.mendez@medicitas.test', Especialidad::MedicinaGeneral],
            ['Dr. Mateo Herrera', 'mateo.herrera@medicitas.test', Especialidad::MedicinaGeneral],
            ['Dr. Andrés Rojas', 'andres.rojas@medicitas.test', Especialidad::Cardiologia],
            ['Dra. Camila Torres', 'camila.torres@medicitas.test', Especialidad::Pediatria],
            ['Dr. Felipe Ortiz', 'felipe.ortiz@medicitas.test', Especialidad::Dermatologia],
            ['Dra. Valentina Ríos', 'valentina.rios@medicitas.test', Especialidad::Ginecologia],
        ];

        foreach ($medicos as [$nombre, $email, $especialidad]) {
            User::factory()->medico($especialidad)->create([
                'name' => $nombre,
                'email' => $email,
            ]);
        }
    }
}
