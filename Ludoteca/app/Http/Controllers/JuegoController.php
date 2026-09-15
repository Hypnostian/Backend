<?php

namespace App\Http\Controllers;

use App\Models\Juego;
use App\Models\Idioma;
use App\Models\Expansion;
use Illuminate\Http\Request;

class JuegoController extends Controller
{
    public function index()
    {
        $juegos = Juego::with('idioma')->orderBy('titulo')->get();

        return view('juegos.index', compact('juegos'));
    }

    public function search(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'anio_desde' => 'nullable|integer|min:1900|max:2100',
            'anio_hasta' => 'nullable|integer|min:1900|max:2100|after_or_equal:anio_desde',
            'idioma_id' => 'nullable|integer|exists:idiomas,id',
        ]);

        $term = trim($filters['q'] ?? '');

        $juegos = Juego::with(['idioma', 'expansiones.idioma'])
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($query) use ($term) {
                    $query->where('titulo', 'like', "%{$term}%")
                        ->orWhereHas('expansiones', function ($query) use ($term) {
                            $query->where('titulo', 'like', "%{$term}%");
                        });
                });
            })
            ->when(isset($filters['anio_desde']), fn ($query) => $query->where('anio', '>=', $filters['anio_desde']))
            ->when(isset($filters['anio_hasta']), fn ($query) => $query->where('anio', '<=', $filters['anio_hasta']))
            ->when(isset($filters['idioma_id']), function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->where('idioma_id', $filters['idioma_id'])
                        ->orWhereHas('expansiones', function ($query) use ($filters) {
                            $query->where('idioma_id', $filters['idioma_id']);
                        });
                });
            })
            ->orderBy('titulo')
            ->get();

        $expansiones = Expansion::with(['juego', 'idioma'])
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($query) use ($term) {
                    $query->where('titulo', 'like', "%{$term}%")
                        ->orWhereHas('juego', function ($query) use ($term) {
                            $query->where('titulo', 'like', "%{$term}%");
                        });
                });
            })
            ->when(isset($filters['anio_desde']), fn ($query) => $query->whereHas('juego', fn ($query) => $query->where('anio', '>=', $filters['anio_desde'])))
            ->when(isset($filters['anio_hasta']), fn ($query) => $query->whereHas('juego', fn ($query) => $query->where('anio', '<=', $filters['anio_hasta'])))
            ->when(isset($filters['idioma_id']), fn ($query) => $query->where('idioma_id', $filters['idioma_id']))
            ->orderBy('titulo')
            ->get();

        $idiomas = Idioma::orderBy('nombre')->get();

        return view('juegos.search', compact('juegos', 'expansiones', 'idiomas', 'filters'));
    }

    public function show(Juego $juego)
    {
        $juego->load(['idioma', 'expansiones']);

        return view('juegos.show', compact('juego'));
    }

    public function create()
    {
        $idiomas = Idioma::orderBy('nombre')->get();

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

    public function edit(Juego $juego)
    {
        $idiomas = Idioma::orderBy('nombre')->get();

        return view('juegos.edit', compact('juego', 'idiomas'));
    }

    public function update(Request $request, Juego $juego)
    {
        $request->validate([
            'titulo' => 'required|string|max:100',
            'anio' => 'required|integer|min:1900|max:2100',
            'idioma_id' => 'required|integer|exists:idiomas,id',
        ]);

        $juego->update($request->only(['titulo', 'anio', 'idioma_id']));

        return redirect()->route('juegos.show', $juego)->with('success', 'Juego actualizado correctamente');
    }

    public function destroy(Juego $juego)
    {
        $juego->delete();

        return redirect()->route('juegos.index')->with('success', 'Juego eliminado correctamente');
    }
}
?>
