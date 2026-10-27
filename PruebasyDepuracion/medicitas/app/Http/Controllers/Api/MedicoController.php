<?php

namespace App\Http\Controllers\Api;

use App\Enums\Especialidad;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * GET /api/medicos?especialidad=Cardiología: los médicos que se pueden elegir al agendar.
 */
class MedicoController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'especialidad' => ['nullable', Rule::enum(Especialidad::class)],
        ]);

        $medicos = User::medicos()
            ->when($validated['especialidad'] ?? null, fn ($query, $especialidad) => $query->where('especialidad', $especialidad))
            ->orderBy('especialidad')
            ->orderBy('name')
            ->get(['id', 'name', 'especialidad']);

        return response()->json([
            'data' => $medicos->map(fn (User $medico) => [
                'id' => $medico->id,
                'nombre' => $medico->name,
                'especialidad' => $medico->especialidad,
            ]),
        ]);
    }
}
