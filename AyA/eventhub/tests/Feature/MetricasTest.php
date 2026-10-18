<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Módulo 3: Métricas y Reportes Globales (RF 3.1, RF 3.2, RNF 2.2).
 */
class MetricasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_consulta_el_total_de_eventos_y_el_recaudo(): void
    {
        Evento::factory()->count(3)->create();
        $admin = User::factory()->admin()->create();

        $this->withToken($admin->createToken('pruebas')->plainTextToken)
            ->getJson('/api/admin/metricas')
            ->assertOk()
            ->assertExactJson([
                'total_eventos' => 3,
                'recaudo_total' => 48750000,
                'moneda' => 'COP',
            ]);
    }

    public function test_un_organizador_no_puede_ver_las_metricas(): void
    {
        $organizador = User::factory()->organizador()->create();

        $this->withToken($organizador->createToken('pruebas')->plainTextToken)
            ->getJson('/api/admin/metricas')
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo un administrador puede consultar las métricas de la plataforma.');
    }

    public function test_un_asistente_no_puede_ver_las_metricas(): void
    {
        $asistente = User::factory()->create();

        $this->withToken($asistente->createToken('pruebas')->plainTextToken)
            ->getJson('/api/admin/metricas')
            ->assertForbidden();
    }

    public function test_consultar_las_metricas_sin_token_responde_401(): void
    {
        $this->getJson('/api/admin/metricas')->assertUnauthorized();
    }

    public function test_el_gate_ver_metricas_solo_autoriza_al_rol_admin(): void
    {
        $this->assertTrue(Gate::forUser(User::factory()->admin()->make())->allows('ver-metricas'));
        $this->assertTrue(Gate::forUser(User::factory()->organizador()->make())->denies('ver-metricas'));
        $this->assertTrue(Gate::forUser(User::factory()->make())->denies('ver-metricas'));
    }
}
