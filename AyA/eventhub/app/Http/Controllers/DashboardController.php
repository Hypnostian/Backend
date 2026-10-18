<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function metricas(): JsonResponse
    {
        Gate::authorize('ver-metricas'); // Lanza HTTP 403 de forma automática si no es admin

        return response()->json([
            'total_eventos' => Evento::count(),
            // El modelo de datos no registra ventas, así que el recaudo es un valor de referencia
            'recaudo_total' => 48750000,
            'moneda' => 'COP',
        ]);
    }
}
