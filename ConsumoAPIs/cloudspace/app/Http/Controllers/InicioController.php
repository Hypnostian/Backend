<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class InicioController extends Controller
{
    /**
     * Página de inicio con los espacios que se pueden reservar y pagar.
     */
    public function __invoke(): View
    {
        return view('inicio', ['espacios' => config('cloudspace.espacios')]);
    }
}
