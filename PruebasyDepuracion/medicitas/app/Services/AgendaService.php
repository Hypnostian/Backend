<?php

namespace App\Services;

use App\Enums\EstadoCita;
use App\Mail\NuevaCitaAgendada;
use App\Models\Cita;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Lógica compartida por el módulo web y la API: consultar y agendar citas.
 */
class AgendaService
{
    /**
     * Citas ordenadas por fecha, opcionalmente filtradas por estado.
     *
     * @return Collection<int, Cita>
     */
    public function citas(?EstadoCita $estado = null): Collection
    {
        return Cita::query()
            // Eager loading: 1 consulta para los pacientes y 1 para los médicos, sin importar cuántas citas haya.
            // Sin esto cada cita dispara 2 consultas más al leer $cita->paciente y $cita->medico (problema N+1).
            ->with(['paciente', 'medico'])
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->orderBy('fecha_hora')
            ->get();
    }

    /**
     * Cantidad de citas por estado, para los filtros del listado.
     *
     * @return array<string, int>
     */
    public function totalesPorEstado(): array
    {
        $totales = Cita::query()
            ->toBase()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return collect(EstadoCita::cases())
            ->mapWithKeys(fn (EstadoCita $estado) => [$estado->value => (int) ($totales[$estado->value] ?? 0)])
            ->all();
    }

    /**
     * Guarda la cita del paciente (queda "pendiente") y notifica por correo al médico asignado.
     *
     * @param  array{doctor_id: int, especialidad: string, fecha_hora: string, motivo: string}  $datos
     */
    public function agendar(User $paciente, array $datos): Cita
    {
        $cita = $paciente->citasComoPaciente()->create($datos);
        $cita->setRelation('paciente', $paciente)->load('medico');

        try {
            Mail::to($cita->medico)->send(new NuevaCitaAgendada($cita));
        } catch (Throwable $e) {
            // La cita ya quedó guardada: si el servidor de correo falla, se registra el error y se sigue
            report($e);
        }

        return $cita;
    }
}
