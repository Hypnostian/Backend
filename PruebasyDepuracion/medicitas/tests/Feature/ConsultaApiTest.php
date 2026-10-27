<?php

namespace Tests\Feature;

use App\Enums\Especialidad;
use App\Models\Cita;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/citas y GET /api/medicos: consultas equivalentes al listado y al formulario web.
 */
class ConsultaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_filtro_devuelve_todas_las_citas_ordenadas_por_fecha(): void
    {
        Cita::factory()->confirmada()->create(['fecha_hora' => now()->addDays(3)]);
        Cita::factory()->create(['fecha_hora' => now()->addDay()]);
        Cita::factory()->cancelada()->create(['fecha_hora' => now()->addDays(2)]);

        $response = $this->getJson('/api/citas')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'especialidad', 'fecha_hora', 'estado', 'motivo',
                        'paciente' => ['id', 'nombre'],
                        'medico' => ['id', 'nombre', 'especialidad'],
                        'creada_en',
                    ],
                ],
            ]);

        $this->assertSame(['pendiente', 'cancelada', 'confirmada'], $response->collect('data')->pluck('estado')->all());
    }

    public function test_filtra_las_citas_pendientes_y_canceladas(): void
    {
        Cita::factory()->confirmada()->create();
        Cita::factory()->count(2)->create();
        Cita::factory()->cancelada()->create();

        $this->getJson('/api/citas?estado=pendiente')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/citas?estado=cancelada')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_un_estado_invalido_responde_422(): void
    {
        $this->getJson('/api/citas?estado=perdida')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['estado']);
    }

    public function test_los_listados_no_cargan_relaciones_de_forma_diferida(): void
    {
        Cita::factory()->count(3)->create();

        // Con preventLazyLoading, leer $cita->paciente sin haberlo cargado con with() lanza una excepción
        Model::preventLazyLoading();

        try {
            $this->withoutExceptionHandling();

            $this->getJson('/api/citas')->assertOk()->assertJsonCount(3, 'data');
            $this->get('/citas')->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_lista_los_medicos_por_especialidad(): void
    {
        $pediatra = User::factory()->medico(Especialidad::Pediatria)->create(['name' => 'Dra. Camila Torres']);
        User::factory()->medico(Especialidad::Cardiologia)->create();
        User::factory()->create();

        $this->getJson('/api/medicos')->assertOk()->assertJsonCount(2, 'data');

        $this->getJson('/api/medicos?especialidad=Pediatría')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    ['id' => $pediatra->id, 'nombre' => 'Dra. Camila Torres', 'especialidad' => 'Pediatría'],
                ],
            ]);
    }
}
