<?php

namespace App\Http\Controllers;

use App\Models\Idioma;
use Illuminate\Http\Request;

class IdiomaController extends Controller
{
    public function index()
    {
        $idiomas = Idioma::orderBy('nombre')->get();

        return view('idiomas.index', compact('idiomas'));
    }

    public function show(Idioma $idioma)
    {
        return view('idiomas.show', compact('idioma'));
    }

    public function create()
    {
        return view('idiomas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50',
            'codigo' => 'required|string|max:5',
        ]);

        Idioma::create($request->only(['nombre', 'codigo']));

        return redirect()->route('idiomas.index')->with('success', 'Idioma creado correctamente');
    }

    public function edit(Idioma $idioma)
    {
        return view('idiomas.edit', compact('idioma'));
    }

    public function update(Request $request, Idioma $idioma)
    {
        $request->validate([
            'nombre' => 'required|string|max:50',
            'codigo' => 'required|string|max:5',
        ]);

        $idioma->update($request->only(['nombre', 'codigo']));

        return redirect()->route('idiomas.show', $idioma)->with('success', 'Idioma actualizado correctamente');
    }

    public function destroy(Idioma $idioma)
    {
        $idioma->delete();

        return redirect()->route('idiomas.index')->with('success', 'Idioma eliminado correctamente');
    }
}
?>
