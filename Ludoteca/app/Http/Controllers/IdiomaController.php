<?php

namespace App\Http\Controllers;

use App\Models\Idioma;
use Illuminate\Http\Request;

class IdiomaController extends Controller
{
    public function index()
    {
        $idiomas = Idioma::all();

        return view('idiomas.index', compact('idiomas'));
    }

    public function show($id)
    {
        $idioma = Idioma::find($id);

        if (! $idioma) {
            abort(404, 'Idioma no encontrado');
        }

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

    public function edit($id)
    {
        $idioma = Idioma::find($id);

        if (! $idioma) {
            abort(404, 'Idioma no encontrado');
        }

        return view('idiomas.edit', compact('idioma'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:50',
            'codigo' => 'required|string|max:5',
        ]);

        Idioma::update($id, $request->only(['nombre', 'codigo']));

        return redirect()->route('idiomas.show', $id)->with('success', 'Idioma actualizado correctamente');
    }

    public function destroy($id)
    {
        Idioma::destroy($id);

        return redirect()->route('idiomas.index')->with('success', 'Idioma eliminado correctamente');
    }
}
?>
