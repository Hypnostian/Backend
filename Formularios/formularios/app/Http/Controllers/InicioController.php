<?php

namespace App\Http\Controllers;

use App\Models\Plato;
use Illuminate\View\View;

class InicioController extends Controller
{
    /**
     * Página de inicio con los últimos platos agregados a la carta.
     */
    public function __invoke(): View
    {
        $recientes = Plato::latest('id')->take(3)->get();
        $totalPlatos = Plato::count();

        return view('inicio', compact('recientes', 'totalPlatos'));
    }
}
