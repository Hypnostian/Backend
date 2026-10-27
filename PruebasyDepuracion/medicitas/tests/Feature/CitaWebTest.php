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
 * RF 2: módulo web con vistas Blade (listado de citas y formulario de agendamiento).
 */
class CitaWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_listado_muestra_las_citas_con_paciente_y_medico(): void
    {
        $paciente = User::factory()->create(['name' => 'Juan Sebastian Lizcano Jaimes']);
        $medico = User::factory()->medico(Especialidad::Pediatria)->create(['name' => 'Dra. Camila Torres']);
        Cita::factory()->confirmada()->create([
            'patient_id' => $paciente->id,
            'doctor_id' => $medico->id,
            'motivo' => 'Control de crecimiento y vacunas.',
        ]);

        $this->get('/citas')
            ->assertOk()
            ->assertViewIs('citas.index')
            ->assertSee('Juan Sebastian Lizcano Jaimes')
            ->assertSee('Dra. Camila Torres')
            ->assertSee('Pediatría')
            ->assertSee('Control de crecimiento y vacunas.')
            ->assertSee('Confirmada');
    }

    public function test_la_raiz_redirige_al_listado(): void
    {
        $this->get('/')->assertRedirect('/citas');
    }

    public function test_listado_filtra_por_estado(): void
    {
        Cita::factory()->confirmada()->create(['motivo' => 'Motivo de la cita confirmada']);
        Cita::factory()->create(['motivo' => 'Motivo de la cita pendiente']);
        Cita::factory()->cancelada()->create(['motivo' => 'Motivo de la cita cancelada']);

        $this->get('/citas?estado=confirmada')
            ->assertOk()
            ->assertSee('Motivo de la cita confirmada')
            ->assertDontSee('Motivo de la cita pendiente')
            ->assertDontSee('Motivo de la cita cancelada');

        $this->get('/citas?estado=cancelada')
            ->assertOk()
            ->assertSee('Motivo de la cita cancelada')
            ->assertDontSee('Motivo de la cita confirmada');
    }

    public function test_un_estado_desconocido_muestra_todas_las_citas(): void
    {
        Cita::factory()->confirmada()->create(['motivo' => 'Motivo de la cita confirmada']);
        Cita::factory()->create(['motivo' => 'Motivo de la cita pendiente']);

        $this->get('/citas?estado=perdida')
            ->assertOk()
            ->assertSee('Motivo de la cita confirmada')
            ->assertSee('Motivo de la cita pendiente');
    }

    public function test_listado_web_no_tiene_problema_n_mas_1(): void
    {
        Cita::factory()->count(10)->create();

        $consultas = 0;
        DB::listen(function (QueryExecuted $query) use (&$consultas) {
            $consultas++;
        });

        $this->get('/citas')->assertOk();

        // Citas + pacientes + médicos + totales por estado de los filtros
        $this->assertLessThanOrEqual(4, $consultas, "Se detectó un problema de N+1 en la vista. Consultas ejecutadas: {$consultas}");
    }

    public function test_invitado_es_redirigido_al_login_para_agendar(): void
    {
        $this->get('/citas/agendar')->assertRedirect('/login');
        $this->post('/citas', [])->assertRedirect('/login');
    }

    public function test_paciente_ve_el_formulario_con_los_medicos(): void
    {
        $paciente = User::factory()->create();
        User::factory()->medico(Especialidad::Cardiologia)->create(['name' => 'Dr. Andrés Rojas']);
        User::factory()->medico(Especialidad::Dermatologia)->create(['name' => 'Dr. Felipe Ortiz']);

        $this->actingAs($paciente)
            ->get('/citas/agendar')
            ->assertOk()
            ->assertViewIs('citas.create')
            ->assertSee('Dr. Andrés Rojas')
            ->assertSee('Dr. Felipe Ortiz')
            ->assertSee('Cardiología')
            ->assertSee('name="fecha_hora"', false)
            ->assertSee('name="motivo"', false);
    }

    public function test_paciente_agenda_desde_el_formulario_y_se_notifica_al_medico(): void
    {
        Mail::fake();

        $paciente = User::factory()->create();
        $medico = User::factory()->medico(Especialidad::Dermatologia)->create(['name' => 'Dr. Felipe Ortiz']);
        $fechaHora = now()->addDays(3)->setTime(15, 0);

        $this->actingAs($paciente)
            ->post('/citas', [
                'especialidad' => 'Dermatología',
                'doctor_id' => $medico->id,
                // Formato que envía el input datetime-local del navegador
                'fecha_hora' => $fechaHora->format('Y-m-d\TH:i'),
                'motivo' => 'Revisión de un lunar que cambió de color.',
            ])
            ->assertRedirect('/citas')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('citas', [
            'patient_id' => $paciente->id,
            'doctor_id' => $medico->id,
            'fecha_hora' => $fechaHora->format('Y-m-d H:i:s'),
            'estado' => 'pendiente',
        ]);

        Mail::assertSent(NuevaCitaAgendada::class, fn (NuevaCitaAgendada $mail) => $mail->hasTo($medico->email));

        $this->get('/citas')->assertSee('Dr. Felipe Ortiz')->assertSee('Pendiente');
    }

    public function test_formulario_muestra_los_errores_de_validacion(): void
    {
        Mail::fake();

        $paciente = User::factory()->create();

        $this->actingAs($paciente)
            ->from('/citas/agendar')
            ->post('/citas', [])
            ->assertRedirect('/citas/agendar')
            ->assertSessionHasErrors(['especialidad', 'doctor_id', 'fecha_hora', 'motivo']);

        $this->assertDatabaseCount('citas', 0);
        Mail::assertNothingSent();

        // Al seguir la redirección, el formulario muestra cada error junto a su campo
        $this->actingAs($paciente)
            ->from('/citas/agendar')
            ->followingRedirects()
            ->post('/citas', ['motivo' => 'Dolor'])
            ->assertOk()
            ->assertSee('Revisa los campos marcados para poder agendar la cita.')
            ->assertSee('El campo especialidad es obligatorio.')
            ->assertSee('El campo motivo de la consulta debe tener al menos 10 caracteres.')
            ->assertSee('Dolor');
    }

    public function test_medico_no_puede_agendar_citas(): void
    {
        Mail::fake();

        $medico = User::factory()->medico()->create();

        $this->actingAs($medico)->get('/citas/agendar')
            ->assertForbidden()
            ->assertSee('Solo los pacientes pueden agendar citas.');

        $this->actingAs($medico)->post('/citas', [])->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_correo_al_medico_muestra_los_datos_de_la_cita(): void
    {
        $cita = Cita::factory()->create([
            'patient_id' => User::factory()->create(['name' => 'Juan Sebastian Lizcano Jaimes']),
            'doctor_id' => User::factory()->medico(Especialidad::Cardiologia)->create(['name' => 'Dr. Andrés Rojas']),
            'motivo' => 'Dolor en el pecho al hacer ejercicio.',
        ]);

        $correo = new NuevaCitaAgendada($cita);

        $correo->assertHasSubject('Nueva cita agendada: '.$cita->fecha_hora->translatedFormat('j \d\e F, g:i a'));
        $correo->assertSeeInHtml('Hola, Dr. Andrés Rojas');
        $correo->assertSeeInHtml('Juan Sebastian Lizcano Jaimes');
        $correo->assertSeeInHtml('Cardiología');
        $correo->assertSeeInHtml('Dolor en el pecho al hacer ejercicio.');
        $correo->assertSeeInText('Paciente: Juan Sebastian Lizcano Jaimes');
    }
}
