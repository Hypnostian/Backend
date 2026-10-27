<?php

namespace Tests\Feature;

use App\Enums\Especialidad;
use App\Models\Cita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

/**
 * POST /api/citas: autenticación, autorización y reglas de validación al agendar.
 */
class AgendarCitaValidacionTest extends TestCase
{
    use RefreshDatabase;

    private User $paciente;

    private User $cardiologo;

    private Carbon $fechaHora;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->paciente = User::factory()->create();
        $this->cardiologo = User::factory()->medico(Especialidad::Cardiologia)->create();
        $this->fechaHora = now()->addDays(5)->setTime(10, 0);
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datos(array $cambios = []): array
    {
        return array_merge([
            'doctor_id' => $this->cardiologo->id,
            'especialidad' => 'Cardiología',
            'fecha_hora' => $this->fechaHora->format('Y-m-d H:i'),
            'motivo' => 'Control de presión arterial.',
        ], $cambios);
    }

    public function test_un_invitado_no_puede_agendar(): void
    {
        $this->postJson('/api/citas', $this->datos())
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No has iniciado sesión.');

        $this->assertDatabaseCount('citas', 0);
        Mail::assertNothingSent();
    }

    public function test_un_medico_no_puede_agendar(): void
    {
        $this->actingAs($this->cardiologo)
            ->postJson('/api/citas', $this->datos())
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo los pacientes pueden agendar citas.');

        $this->assertDatabaseCount('citas', 0);
        Mail::assertNothingSent();
    }

    public function test_los_campos_son_obligatorios(): void
    {
        $this->actingAs($this->paciente)
            ->postJson('/api/citas', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['especialidad', 'doctor_id', 'fecha_hora', 'motivo'])
            ->assertJsonPath('errors.motivo.0', 'El campo motivo de la consulta es obligatorio.');

        $this->assertDatabaseCount('citas', 0);
        Mail::assertNothingSent();
    }

    public function test_la_fecha_debe_ser_futura(): void
    {
        $this->actingAs($this->paciente)
            ->postJson('/api/citas', $this->datos(['fecha_hora' => now()->subDay()->format('Y-m-d H:i')]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fecha_hora' => 'La cita debe ser en una fecha y hora futuras.']);
    }

    public function test_la_especialidad_debe_existir(): void
    {
        $this->actingAs($this->paciente)
            ->postJson('/api/citas', $this->datos(['especialidad' => 'Odontología']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['especialidad']);
    }

    public function test_el_medico_debe_atender_la_especialidad_elegida(): void
    {
        $this->actingAs($this->paciente)
            ->postJson('/api/citas', $this->datos(['especialidad' => 'Pediatría']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['doctor_id' => 'El médico seleccionado no atiende la especialidad elegida.']);
    }

    public function test_no_se_puede_asignar_un_paciente_como_medico(): void
    {
        $otroPaciente = User::factory()->create();

        $this->actingAs($this->paciente)
            ->postJson('/api/citas', $this->datos(['doctor_id' => $otroPaciente->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['doctor_id']);
    }

    public function test_el_motivo_debe_tener_al_menos_10_caracteres(): void
    {
        $this->actingAs($this->paciente)
            ->postJson('/api/citas', $this->datos(['motivo' => 'Dolor']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['motivo']);
    }

    public function test_el_medico_no_puede_tener_dos_citas_a_la_misma_hora(): void
    {
        Cita::factory()->confirmada()->create([
            'doctor_id' => $this->cardiologo->id,
            'fecha_hora' => $this->fechaHora,
        ]);

        $this->actingAs($this->paciente)
            ->postJson('/api/citas', $this->datos())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fecha_hora' => 'El médico ya tiene una cita agendada en esa fecha y hora.']);

        $this->assertDatabaseCount('citas', 1);
    }

    public function test_una_cita_cancelada_libera_el_horario(): void
    {
        Cita::factory()->cancelada()->create([
            'doctor_id' => $this->cardiologo->id,
            'fecha_hora' => $this->fechaHora,
        ]);

        $this->actingAs($this->paciente)
            ->postJson('/api/citas', $this->datos())
            ->assertCreated();

        $this->assertDatabaseCount('citas', 2);
    }

    public function test_el_estado_siempre_empieza_en_pendiente(): void
    {
        $this->actingAs($this->paciente)
            ->postJson('/api/citas', $this->datos(['estado' => 'confirmada', 'patient_id' => $this->cardiologo->id]))
            ->assertCreated()
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.paciente.id', $this->paciente->id);
    }

    public function test_si_falla_el_correo_la_cita_queda_guardada(): void
    {
        Exceptions::fake();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Servidor SMTP no disponible'));

        $this->actingAs($this->paciente)
            ->postJson('/api/citas', $this->datos())
            ->assertCreated();

        $this->assertDatabaseHas('citas', ['patient_id' => $this->paciente->id, 'doctor_id' => $this->cardiologo->id]);
        Exceptions::assertReported(fn (RuntimeException $e) => $e->getMessage() === 'Servidor SMTP no disponible');
    }
}
