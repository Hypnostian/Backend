<?php

namespace App\Http\Controllers;

use App\Enums\Especialidad;
use App\Enums\EstadoCita;
use App\Http\Requests\AgendarCitaRequest;
use App\Models\User;
use App\Services\AgendaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Módulo web: listado de citas y formulario de agendamiento.
 */
class CitaController extends Controller
{
    /**
     * GET /citas?estado=confirmada
     */
    public function index(Request $request, AgendaService $agenda): View
    {
        $filtro = $request->query('estado');
        $estado = is_string($filtro) ? EstadoCita::tryFrom($filtro) : null;

        return view('citas.index', [
            'citas' => $agenda->citas($estado),
            'estado' => $estado,
            'totales' => $agenda->totalesPorEstado(),
        ]);
    }

    /**
     * GET /citas/agendar
     */
    public function create(): View
    {
        return view('citas.create', [
            'especialidades' => Especialidad::cases(),
            'medicos' => User::medicos()->orderBy('name')->get(['id', 'name', 'especialidad'])->groupBy(
                fn (User $medico) => $medico->especialidad->value,
            ),
        ]);
    }

    /**
     * POST /citas
     */
    public function store(AgendarCitaRequest $request, AgendaService $agenda): RedirectResponse
    {
        $cita = $agenda->agendar($request->user(), $request->validated());

        return redirect()
            ->route('citas.index')
            ->with('exito', sprintf(
                'Tu cita de %s con %s quedó agendada para el %s y está pendiente de confirmación.',
                $cita->especialidad->value,
                $cita->medico->name,
                $cita->fecha_hora->translatedFormat('l j \d\e F \a \l\a\s g:i a'),
            ));
    }
}
