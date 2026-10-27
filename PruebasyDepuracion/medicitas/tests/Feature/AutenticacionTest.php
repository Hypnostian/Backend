<?php

namespace Tests\Feature;

use App\Enums\Especialidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Inicio de sesión web (sesión) y por API (tokens de Sanctum).
 */
class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_paciente_inicia_sesion_y_vuelve_al_formulario(): void
    {
        $paciente = User::factory()->create(['email' => 'jlizcano471@unab.edu.co']);

        $this->get('/citas/agendar')->assertRedirect('/login');

        $this->post('/login', ['email' => 'jlizcano471@unab.edu.co', 'password' => 'password'])
            ->assertRedirect('/citas/agendar');

        $this->assertAuthenticatedAs($paciente);
    }

    public function test_credenciales_incorrectas_no_inician_sesion(): void
    {
        User::factory()->create(['email' => 'jlizcano471@unab.edu.co']);

        $this->from('/login')
            ->post('/login', ['email' => 'jlizcano471@unab.edu.co', 'password' => 'incorrecta'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'El correo o la contraseña no son correctos.']);

        $this->assertGuest();
    }

    public function test_cerrar_sesion(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/citas');

        $this->assertGuest();
    }

    public function test_login_por_api_entrega_un_token_para_agendar(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'jlizcano471@unab.edu.co']);
        $medico = User::factory()->medico(Especialidad::MedicinaGeneral)->create();

        $token = $this->postJson('/api/login', ['email' => 'jlizcano471@unab.edu.co', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('usuario.rol', 'paciente')
            ->json('token');

        $this->withToken($token)
            ->postJson('/api/citas', [
                'doctor_id' => $medico->id,
                'especialidad' => 'Medicina general',
                'fecha_hora' => now()->addDays(2)->setTime(8, 0)->format('Y-m-d H:i'),
                'motivo' => 'Chequeo general anual.',
            ])
            ->assertCreated();

        $this->assertDatabaseCount('citas', 1);
    }

    public function test_login_por_api_con_credenciales_incorrectas_responde_401(): void
    {
        User::factory()->create(['email' => 'jlizcano471@unab.edu.co']);

        $this->postJson('/api/login', ['email' => 'jlizcano471@unab.edu.co', 'password' => 'incorrecta'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'El correo o la contraseña no son correctos.');
    }

    public function test_logout_por_api_revoca_el_token(): void
    {
        $paciente = User::factory()->create();
        $token = $paciente->createToken('medicitas-api')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
