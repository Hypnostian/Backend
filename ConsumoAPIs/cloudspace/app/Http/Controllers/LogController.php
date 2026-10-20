<?php

namespace App\Http\Controllers;

use App\Models\Registro;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    /**
     * Logs de las integraciones: eventos y errores de Stripe, el webhook y SendGrid.
     */
    public function index(Request $request): View
    {
        $filtro = (string) $request->query('filtro', 'todos');

        $registros = Registro::query()
            ->when($filtro === 'problemas', fn ($consulta) => $consulta->problemas())
            ->when(array_key_exists($filtro, Registro::SERVICIOS), fn ($consulta) => $consulta->where('servicio', $filtro))
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('logs.index', [
            'registros' => $registros,
            'filtro' => $filtro,
            'filtros' => ['todos' => 'Todos', 'problemas' => 'Errores y advertencias', ...Registro::SERVICIOS],
            'errores' => Registro::whereIn('nivel', Registro::NIVELES_DE_ERROR)->count(),
            'advertencias' => Registro::where('nivel', 'warning')->count(),
            'total' => Registro::count(),
        ]);
    }
}
