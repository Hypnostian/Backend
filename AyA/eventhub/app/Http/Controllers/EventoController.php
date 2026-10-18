<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Evento::with('organizador:id,name')->get());
    }

    public function update(Request $request, Evento $evento): JsonResponse
    {
        // Laravel busca la EventoPolicy y ejecuta 'update(user, evento)'
        Gate::authorize('update', $evento);

        $datos = $request->validate([
            'titulo' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'cupo_maximo' => 'required|integer|min:1',
        ]);

        $evento->update($datos);

        return response()->json([
            'message' => 'Evento actualizado con éxito',
            'evento' => $evento->load('organizador:id,name'),
        ]);
    }
}
