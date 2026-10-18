<?php

namespace Database\Seeders;

use App\Models\Evento;
use App\Models\User;
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
        // Crear Administrador Global
        User::create([
            'name' => 'Admin Global',
            'email' => 'admin@eventhub.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Crear Organizador A
        $organizadorA = User::create([
            'name' => 'Laura Méndez',
            'email' => 'laura@eventhub.com',
            'password' => bcrypt('password'),
            'role' => 'organizador',
        ]);

        // Crear Organizador B
        $organizadorB = User::create([
            'name' => 'Carlos Rojas',
            'email' => 'carlos@eventhub.com',
            'password' => bcrypt('password'),
            'role' => 'organizador',
        ]);

        // Crear Asistente
        User::create([
            'name' => 'Sofía Torres',
            'email' => 'sofia@eventhub.com',
            'password' => bcrypt('password'),
            'role' => 'asistente',
        ]);

        // Eventos registrados a nombre del Organizador A (IDs 1 y 2)
        Evento::create([
            'user_id' => $organizadorA->id,
            'titulo' => 'Laravel Conf Colombia 2026',
            'descripcion' => 'Conferencia sobre desarrollo backend con Laravel, APIs REST y buenas prácticas de seguridad.',
            'cupo_maximo' => 300,
        ]);

        Evento::create([
            'user_id' => $organizadorA->id,
            'titulo' => 'Taller de APIs seguras con Sanctum',
            'descripcion' => 'Sesión práctica de autenticación con tokens, Gates y Policies.',
            'cupo_maximo' => 40,
        ]);

        // Evento registrado a nombre del Organizador B (ID 3)
        Evento::create([
            'user_id' => $organizadorB->id,
            'titulo' => 'Cloud & DevOps Summit Bucaramanga',
            'descripcion' => 'Encuentro sobre contenedores, despliegue continuo y arquitectura en la nube.',
            'cupo_maximo' => 150,
        ]);
    }
}
