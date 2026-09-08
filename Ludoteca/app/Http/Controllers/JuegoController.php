<?php

namespace App\Http\Controllers;

use App\Models\Juego;
use App\Models\Idioma;
use Illuminate\Http\Request;

class JuegoController extends Controller
{
    public function index()
    {
        $juegos = Juego::all();

        return view('juegos.index', compact('juegos'));
    }

    public function show($id)
    {
        $juego = Juego::find($id);

        if (! $juego) {
            abort(404, 'Juego no encontrado');
        }

        return view('juegos.show', compact('juego'));
    }

    public function create()
    {
        $idiomas = Idioma::all();

        return view('juegos.create', compact('idiomas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:100',
            'anio' => 'required|integer|min:1900|max:2100',
            'idioma_id' => 'required|integer|exists:idiomas,id',
        ]);

        Juego::create($request->only(['titulo', 'anio', 'idioma_id']));

        return redirect()->route('juegos.index')->with('success', 'Juego creado correctamente');
    }

    public function edit($id)
    {
        $juego = Juego::find($id);
        $idiomas = Idioma::all();

        if (! $juego) {
            abort(404, 'Juego no encontrado');
        }

        return view('juegos.edit', compact('juego', 'idiomas'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'titulo' => 'required|string|max:100',
            'anio' => 'required|integer|min:1900|max:2100',
            'idioma_id' => 'required|integer|exists:idiomas,id',
        ]);

        Juego::update($id, $request->only(['titulo', 'anio', 'idioma_id']));

        return redirect()->route('juegos.show', $id)->with('success', 'Juego actualizado correctamente');
    }

    public function destroy($id)
    {
        Juego::destroy($id);

        return redirect()->route('juegos.index')->with('success', 'Juego eliminado correctamente');
    }
}
?>
