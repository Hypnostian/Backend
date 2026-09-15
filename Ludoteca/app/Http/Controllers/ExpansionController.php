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
        $expansiones = Expansion::with(['juego', 'idioma'])->orderBy('titulo')->get();

        return view('expansiones.index', compact('expansiones'));
    }

    public function show(Expansion $expansion)
    {
        $expansion->load(['juego', 'idioma']);

        return view('expansiones.show', compact('expansion'));
    }

    public function create()
    {
        $juegos = Juego::orderBy('titulo')->get();
        $idiomas = Idioma::orderBy('nombre')->get();

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

    public function edit(Expansion $expansion)
    {
        $juegos = Juego::orderBy('titulo')->get();
        $idiomas = Idioma::orderBy('nombre')->get();

        return view('expansiones.edit', compact('expansion', 'juegos', 'idiomas'));
    }

    public function update(Request $request, Expansion $expansion)
    {
        $request->validate([
            'titulo' => 'required|string|max:100',
            'juego_id' => 'required|integer|exists:juegos,id',
            'idioma_id' => 'required|integer|exists:idiomas,id',
        ]);

        $expansion->update($request->only(['juego_id', 'titulo', 'idioma_id']));

        return redirect()->route('expansiones.show', $expansion)->with('success', 'Expansión actualizada correctamente');
    }

    public function destroy(Expansion $expansion)
    {
        $expansion->delete();

        return redirect()->route('expansiones.index')->with('success', 'Expansión eliminada correctamente');
    }
}
?>
