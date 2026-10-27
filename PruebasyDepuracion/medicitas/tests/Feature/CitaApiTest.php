<?php

namespace Tests\Feature;

use App\Enums\Especialidad;
use App\Mail\NuevaCitaAgendada;
use App\Models\Cita;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * RT 2: suite de pruebas HTTP/API del taller (filtrado, persistencia con correo y problema N+1).
 */
class CitaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_citas_confirmadas(): void
    {
        Cita::factory()->confirmada()->create(['motivo' => 'Cita confirmada 1']);
        Cita::factory()->confirmada()->create(['motivo' => 'Cita confirmada 2']);
        Cita::factory()->confirmada()->create(['motivo' => 'Cita confirmada 3']);
        Cita::factory()->create(['motivo' => 'Cita pendiente 1']);
        Cita::factory()->create(['motivo' => 'Cita pendiente 2']);
        Cita::factory()->cancelada()->create(['motivo' => 'Cita cancelada 1']);

        $response = $this->getJson('/api/citas?estado=confirmada');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['motivo' => 'Cita confirmada 1'])
            ->assertJsonFragment(['motivo' => 'Cita confirmada 2'])
            ->assertJsonFragment(['motivo' => 'Cita confirmada 3'])
            ->assertJsonMissing(['motivo' => 'Cita pendiente 1'])
            ->assertJsonMissing(['motivo' => 'Cita pendiente 2'])
            ->assertJsonMissing(['motivo' => 'Cita cancelada 1']);

        // Todas las citas devueltas están en estado "confirmada"
        $this->assertSame(['confirmada'], $response->collect('data')->pluck('estado')->unique()->values()->all());
    }

    public function test_agendar_cita_y_envio_correo(): void
    {
        Mail::fake();

        $paciente = User::factory()->create([
            'name' => 'Juan Sebastian Lizcano Jaimes',
            'email' => 'jlizcano471@unab.edu.co',
        ]);
        $medico = User::factory()->medico(Especialidad::Cardiologia)->create();
        $fechaHora = now()->addWeek()->setTime(9, 30);

        $response = $this->actingAs($paciente)
            ->postJson('/api/citas', [
                'doctor_id' => $medico->id,
                'especialidad' => 'Cardiología',
                'fecha_hora' => $fechaHora->format('Y-m-d H:i'),
                'motivo' => 'Control de presión arterial y revisión de exámenes.',
            ]);

        // Imprime el cuerpo de la respuesta para depurar la petición
        dump('POST /api/citas Response Content:');
        dump($response->json());

        $response->assertStatus(201)
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.paciente.id', $paciente->id)
            ->assertJsonPath('data.medico.id', $medico->id);

        $this->assertDatabaseHas('citas', [
            'patient_id' => $paciente->id,
            'doctor_id' => $medico->id,
            'especialidad' => 'Cardiología',
            'fecha_hora' => $fechaHora->format('Y-m-d H:i:s'),
            'estado' => 'pendiente',
            'motivo' => 'Control de presión arterial y revisión de exámenes.',
        ]);

        // El correo se envía una sola vez y va dirigido al médico asignado
        Mail::assertSent(NuevaCitaAgendada::class, function (NuevaCitaAgendada $mail) use ($medico, $paciente) {
            return $mail->hasTo($medico->email)
                && $mail->cita->patient_id === $paciente->id;
        });
        Mail::assertSentCount(1);
    }

    public function test_detecta_problema_n_mas_1(): void
    {
        // 10 citas, cada una con su propio paciente y su propio médico
        Cita::factory()->count(10)->confirmada()->create();

        // Escuchar y guardar cada consulta SQL que se ejecuta durante la petición
        $consultas = [];
        DB::listen(function (QueryExecuted $query) use (&$consultas) {
            $consultas[] = $query->sql;
        });

        $response = $this->getJson('/api/citas');

        // Imprimir la evidencia directamente en la terminal de pruebas
        dump('Total de consultas SQL ejecutadas: '.count($consultas));
        dump($consultas);

        $response->assertStatus(200)->assertJsonCount(10, 'data');

        // Máximo 3 consultas: 1 para las citas, 1 para los pacientes y 1 para los médicos
        $this->assertLessThanOrEqual(3, count($consultas), 'Se detectó un problema de N+1. Consultas ejecutadas: '.count($consultas));
    }
}
