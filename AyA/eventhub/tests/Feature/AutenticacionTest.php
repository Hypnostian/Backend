<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Módulo 1: Identidad y Autenticación (RF 1.1, RF 1.2, RNF 1.1 a RNF 1.3).
 */
class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_login_con_credenciales_validas_emite_un_token_bearer(): void
    {
        $usuario = User::factory()->organizador()->create(['email' => 'laura@eventhub.com']);

        $this->postJson('/api/login', [
            'email' => 'laura@eventhub.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type'])
            ->assertJsonPath('token_type', 'Bearer');

        // Sanctum guarda el token emitido asociado al usuario
        $this->assertCount(1, $usuario->tokens);
    }

    public function test_el_token_emitido_en_el_login_permite_consultar_el_perfil(): void
    {
        User::factory()->create(['email' => 'sofia@eventhub.com']);

        $token = $this->postJson('/api/login', [
            'email' => 'sofia@eventhub.com',
            'password' => 'password',
        ])->json('token');

        $this->withToken($token)
            ->getJson('/api/perfil')
            ->assertOk()
            ->assertJsonPath('email', 'sofia@eventhub.com');
    }

    public function test_el_login_con_contrasena_incorrecta_responde_401(): void
    {
        User::factory()->create(['email' => 'laura@eventhub.com']);

        $this->postJson('/api/login', [
            'email' => 'laura@eventhub.com',
            'password' => 'otra-clave',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Usuario no encontrado o contraseña incorrecta')
            ->assertJsonMissingPath('token');
    }

    public function test_el_login_con_correo_inexistente_responde_401_con_el_mismo_mensaje(): void
    {
        $this->postJson('/api/login', [
            'email' => 'nadie@eventhub.com',
            'password' => 'password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Usuario no encontrado o contraseña incorrecta');
    }

    public function test_el_login_sin_credenciales_responde_422(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_el_login_con_correo_mal_formado_responde_422(): void
    {
        $this->postJson('/api/login', [
            'email' => 'no-es-un-correo',
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_el_perfil_devuelve_los_datos_del_usuario_autenticado_sin_la_contrasena(): void
    {
        $usuario = User::factory()->organizador()->create([
            'name' => 'Laura Méndez',
            'email' => 'laura@eventhub.com',
        ]);

        $this->withToken($usuario->createToken('pruebas')->plainTextToken)
            ->getJson('/api/perfil')
            ->assertOk()
            ->assertJson([
                'id' => $usuario->id,
                'name' => 'Laura Méndez',
                'email' => 'laura@eventhub.com',
                'role' => 'organizador',
            ])
            ->assertJsonMissingPath('password');
    }

    public function test_el_perfil_sin_token_responde_401(): void
    {
        $this->getJson('/api/perfil')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado. Envía un token Bearer válido.');
    }

    public function test_el_perfil_sin_token_responde_401_en_json_aunque_el_cliente_no_lo_pida(): void
    {
        // Sin cabecera "Accept: application/json", como envían algunos clientes HTTP por defecto
        $this->get('/api/perfil')
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('message', 'No autenticado. Envía un token Bearer válido.');
    }

    public function test_el_perfil_con_token_invalido_responde_401(): void
    {
        $this->withToken('1|token-que-no-existe')
            ->getJson('/api/perfil')
            ->assertUnauthorized();
    }

    public function test_las_contrasenas_se_guardan_con_hash_bcrypt(): void
    {
        $usuario = User::create([
            'name' => 'Usuario Nuevo',
            'email' => 'nuevo@eventhub.com',
            'password' => 'secreto123',
        ]);

        $guardada = $usuario->fresh()->password;

        $this->assertNotSame('secreto123', $guardada);
        $this->assertSame('bcrypt', Hash::info($guardada)['algoName']);
        $this->assertTrue(Hash::check('secreto123', $guardada));
    }
}
