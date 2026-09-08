<?php

namespace App\Http\Controllers;

use App\Models\Juego;
use App\Models\Idioma;
use App\Models\Expansion;
use Illuminate\Http\Request;

class ExpansionController extends Controller
{
    public function index()
    {
        $expansiones = Expansion::all();

        return view('expansiones.index', compact('expansiones'));
    }

    public function show($id)
    {
        $expansion = Expansion::find($id);

        if (! $expansion) {
            abort(404, 'Expansión no encontrada');
        }

        return view('expansiones.show', compact('expansion'));
    }

    public function create()
    {
        $juegos = Juego::all();
        $idiomas = Idioma::all();

        return view('expansiones.create', compact('juegos', 'idiomas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:100',
            'juego_id' => 'required|integer|exists:juegos,id',
            'idioma_id' => 'required|integer|exists:idiomas,id',
        ]);

        Expansion::create($request->only(['juego_id', 'titulo', 'idioma_id']));

        return redirect()->route('expansiones.index')->with('success', 'Expansión creada correctamente');
    }

    public function edit($id)
    {
        $expansion = Expansion::find($id);
        $juegos = Juego::all();
        $idiomas = Idioma::all();

        if (! $expansion) {
            abort(404, 'Expansión no encontrada');
        }

        return view('expansiones.edit', compact('expansion', 'juegos', 'idiomas'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'titulo' => 'required|string|max:100',
            'juego_id' => 'required|integer|exists:juegos,id',
            'idioma_id' => 'required|integer|exists:idiomas,id',
        ]);

        Expansion::update($id, $request->only(['juego_id', 'titulo', 'idioma_id']));

        return redirect()->route('expansiones.show', $id)->with('success', 'Expansión actualizada correctamente');
    }

    public function destroy($id)
    {
        Expansion::destroy($id);

        return redirect()->route('expansiones.index')->with('success', 'Expansión eliminada correctamente');
    }
}
?>
