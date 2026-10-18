<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Modelo de datos y datos semilla del taller.
 */
class DatosSemillaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_crea_los_usuarios_y_eventos_de_prueba(): void
    {
        $this->seed();

        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(2, User::where('role', 'organizador')->count());
        $this->assertSame(1, User::where('role', 'asistente')->count());

        // Organizador A (Laura) tiene al menos un evento a su nombre
        $organizadorA = User::where('email', 'laura@eventhub.com')->firstOrFail();
        $this->assertSame('organizador', $organizadorA->role);
        $this->assertGreaterThanOrEqual(1, $organizadorA->eventos()->count());

        foreach (User::all() as $usuario) {
            $this->assertTrue(Hash::check('password', $usuario->password));
        }
    }

    public function test_el_rol_por_defecto_es_asistente(): void
    {
        $usuario = User::create([
            'name' => 'Sin Rol',
            'email' => 'sinrol@eventhub.com',
            'password' => 'password',
        ]);

        $this->assertSame('asistente', $usuario->fresh()->role);
    }

    public function test_el_correo_es_unico(): void
    {
        User::factory()->create(['email' => 'repetido@eventhub.com']);

        $this->expectException(QueryException::class);

        User::factory()->create(['email' => 'repetido@eventhub.com']);
    }

    public function test_al_eliminar_un_organizador_se_eliminan_sus_eventos(): void
    {
        $evento = Evento::factory()->create();

        $evento->organizador->delete();

        $this->assertModelMissing($evento);
    }
}
