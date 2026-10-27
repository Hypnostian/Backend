<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoCita;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgendarCitaRequest;
use App\Http\Resources\CitaResource;
use App\Services\AgendaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Endpoints JSON equivalentes al módulo web de citas.
 */
class CitaController extends Controller
{
    /**
     * GET /api/citas?estado=confirmada
     */
    public function index(Request $request, AgendaService $agenda): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'estado' => ['nullable', Rule::enum(EstadoCita::class)],
        ]);

        $estado = EstadoCita::tryFrom($validated['estado'] ?? '');

        return CitaResource::collection($agenda->citas($estado));
    }

    /**
     * POST /api/citas (paciente autenticado con Sanctum)
     */
    public function store(AgendarCitaRequest $request, AgendaService $agenda): JsonResponse
    {
        $cita = $agenda->agendar($request->user(), $request->validated());

        return CitaResource::make($cita)
            ->response()
            ->setStatusCode(201);
    }
}
