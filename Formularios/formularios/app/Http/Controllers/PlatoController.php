<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlatoRequest;
use App\Http\Requests\UpdatePlatoRequest;
use App\Models\Plato;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PlatoController extends Controller
{
    /**
     * Listado de administración de platos (GET /platos).
     */
    public function index(): View
    {
        $platos = Plato::orderByDesc('id')->paginate(10);

        return view('platos.index', compact('platos'));
    }

    /**
     * Formulario de registro (GET /platos/crear).
     */
    public function create(): View
    {
        return view('platos.create');
    }

    /**
     * Registra un plato nuevo (POST /platos).
     */
    public function store(PlatoRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['imagen'] = $this->guardarImagen($request->file('imagen'), $datos['nombre']);

        try {
            $plato = Plato::create($datos);
        } catch (Throwable $e) {
            // Si falla la base de datos, no se deja la imagen huérfana en el servidor.
            Storage::disk('public')->delete($datos['imagen']);

            throw $e;
        }

        return redirect()
            ->route('platos.create')
            ->with('success', "El plato «{$plato->nombre}» se registró correctamente.")
            ->with('plato_id', $plato->id);
    }

    /**
     * Formulario de edición (GET /platos/{plato}/editar).
     */
    public function edit(Plato $plato): View
    {
        return view('platos.edit', compact('plato'));
    }

    /**
     * Actualiza un plato (PUT /platos/{plato}).
     */
    public function update(UpdatePlatoRequest $request, Plato $plato): RedirectResponse
    {
        $datos = $request->validated();
        $imagenAnterior = $plato->imagen;

        // Sin imagen nueva se conserva la actual; con imagen nueva se guarda en AAAA/MM.
        unset($datos['imagen']);

        if ($request->hasFile('imagen')) {
            $datos['imagen'] = $this->guardarImagen($request->file('imagen'), $datos['nombre']);
        }

        try {
            $plato->update($datos);
        } catch (Throwable $e) {
            if (isset($datos['imagen'])) {
                Storage::disk('public')->delete($datos['imagen']);
            }

            throw $e;
        }

        // La imagen antigua se elimina físicamente solo cuando la nueva ya quedó guardada.
        if (isset($datos['imagen']) && $datos['imagen'] !== $imagenAnterior) {
            Storage::disk('public')->delete($imagenAnterior);
        }

        return redirect()
            ->route('platos.index')
            ->with('success', "El plato «{$plato->nombre}» se actualizó correctamente.");
    }

    /**
     * Elimina un plato con "soft delete" (DELETE /platos/{plato}).
     */
    public function destroy(Plato $plato): RedirectResponse
    {
        // SoftDeletes solo llena deleted_at: el registro y su imagen se conservan.
        $plato->delete();

        return redirect()
            ->route('platos.index')
            ->with('success', "El plato «{$plato->nombre}» se eliminó correctamente.");
    }

    /**
     * Guarda la imagen en storage/app/public/platos/AAAA/MM usando el nombre del plato
     * (p. ej. platos/2026/09/hamburguesa-artesanal.png) y devuelve esa ruta relativa.
     */
    private function guardarImagen(UploadedFile $imagen, string $nombrePlato): string
    {
        $disco = Storage::disk('public');
        $carpeta = 'platos/'.now()->format('Y/m');
        $base = Str::slug($nombrePlato) ?: 'plato';
        $extension = $imagen->extension();

        // Si ya existe un archivo con ese nombre en la carpeta, se agrega un sufijo (-2, -3...).
        $archivo = "{$base}.{$extension}";
        for ($i = 2; $disco->exists("{$carpeta}/{$archivo}"); $i++) {
            $archivo = "{$base}-{$i}.{$extension}";
        }

        $ruta = $imagen->storeAs($carpeta, $archivo, 'public');

        if ($ruta === false) {
            throw ValidationException::withMessages([
                'imagen' => 'No se pudo guardar la imagen en el servidor. Inténtalo de nuevo.',
            ]);
        }

        return $ruta;
    }
}
