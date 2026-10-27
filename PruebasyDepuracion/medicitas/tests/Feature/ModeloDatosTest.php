<?php

namespace Tests\Feature;

use App\Enums\Especialidad;
use App\Enums\EstadoCita;
use App\Enums\Rol;
use App\Models\Cita;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * RF 1.1 y RT 1: estructura de la tabla citas, relaciones con users y base de datos en memoria.
 */
class ModeloDatosTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_pruebas_corren_sobre_sqlite_en_memoria(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
    }

    public function test_la_tabla_citas_tiene_las_columnas_del_requisito(): void
    {
        $this->assertTrue(Schema::hasColumns('citas', [
            'id', 'patient_id', 'doctor_id', 'especialidad', 'fecha_hora', 'estado', 'motivo', 'created_at', 'updated_at',
        ]));
    }

    public function test_el_estado_por_defecto_es_pendiente(): void
    {
        $paciente = User::factory()->create();
        $medico = User::factory()->medico()->create();

        // Inserción directa sin estado: el valor lo pone la base de datos
        DB::table('citas')->insert([
            'patient_id' => $paciente->id,
            'doctor_id' => $medico->id,
            'especialidad' => $medico->especialidad->value,
            'fecha_hora' => now()->addDay(),
            'motivo' => 'Chequeo general anual.',
        ]);

        $this->assertDatabaseHas('citas', ['patient_id' => $paciente->id, 'estado' => 'pendiente']);
    }

    public function test_patient_id_y_doctor_id_son_claves_foraneas_de_users(): void
    {
        $this->expectException(QueryException::class);

        DB::table('citas')->insert([
            'patient_id' => 999,
            'doctor_id' => 998,
            'especialidad' => 'Cardiología',
            'fecha_hora' => now()->addDay(),
            'motivo' => 'Chequeo general anual.',
        ]);
    }

    public function test_relaciones_y_tipos_del_modelo_cita(): void
    {
        $paciente = User::factory()->create();
        $medico = User::factory()->medico(Especialidad::Ginecologia)->create();
        $cita = Cita::factory()->create(['patient_id' => $paciente->id, 'doctor_id' => $medico->id]);

        $this->assertTrue($cita->paciente->is($paciente));
        $this->assertTrue($cita->medico->is($medico));
        $this->assertTrue($paciente->citasComoPaciente->contains($cita));
        $this->assertTrue($medico->citasComoMedico->contains($cita));

        $this->assertInstanceOf(Carbon::class, $cita->fecha_hora);
        $this->assertSame(EstadoCita::Pendiente, $cita->estado);
        $this->assertSame(Especialidad::Ginecologia, $cita->especialidad);
        $this->assertSame(Rol::Paciente, $paciente->rol);
        $this->assertSame(Rol::Medico, $medico->rol);
    }

    public function test_los_seeders_crean_medicos_pacientes_y_citas_en_los_tres_estados(): void
    {
        $this->seed();

        $this->assertSame(6, User::medicos()->count());
        $this->assertSame(8, User::pacientes()->count());
        $this->assertDatabaseHas('users', ['email' => 'jlizcano471@unab.edu.co', 'rol' => 'paciente']);
        $this->assertSame(24, Cita::count());

        foreach (EstadoCita::cases() as $estado) {
            $this->assertTrue(Cita::where('estado', $estado)->exists(), "No hay citas en estado {$estado->value}");
        }

        // Cada cita es de la especialidad que atiende su médico
        Cita::with('medico')->get()->each(
            fn (Cita $cita) => $this->assertSame($cita->medico->especialidad, $cita->especialidad),
        );
    }
}
