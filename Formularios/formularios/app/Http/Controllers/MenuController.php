<?php

namespace App\Http\Controllers;

use App\Models\Plato;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * Carta pública con los platos en orden alfabético (GET /menu).
     */
    public function index(): View
    {
        // Solo se consultan los datos que muestra la carta: nombre y precio.
        $platos = Plato::orderBy('nombre')->get(['id', 'nombre', 'precio']);

        // Secciones por letra inicial (sin tildes): A, B, C...
        $platosPorLetra = $platos->groupBy(
            fn (Plato $plato) => Str::upper(Str::substr(Str::ascii($plato->nombre), 0, 1))
        );

        return view('menu.index', compact('platos', 'platosPorLetra'));
    }

    /**
     * Detalle de un plato de la carta (GET /menu/{plato}).
     */
    public function show(Plato $plato): View
    {
        return view('menu.show', compact('plato'));
    }
}
