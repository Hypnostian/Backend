<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class PacienteSeeder extends Seeder
{
    /**
     * Pacientes de prueba (contraseña: password).
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Juan Sebastian Lizcano Jaimes',
            'email' => 'jlizcano471@unab.edu.co',
        ]);

        User::factory()->count(7)->create();
    }
}
