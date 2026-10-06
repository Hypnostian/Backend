<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Reutiliza las reglas, mensajes y atributos de PlatoRequest.
 */
class UpdatePlatoRequest extends PlatoRequest
{
    /**
     * Mismas reglas del registro, con dos excepciones.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $reglas = parent::rules();

        // 1. El nombre sigue siendo único, pero ignorando el plato que se está editando.
        $reglas['nombre'] = [
            'required', 'string', 'min:3', 'max:100',
            Rule::unique('platos', 'nombre')->ignore($this->route('plato'))->withoutTrashed(),
        ];

        // 2. La imagen es opcional: si no se selecciona una nueva, se conserva la anterior.
        $reglas['imagen'] = ['nullable', 'image', 'mimes:jpg,png,webp', 'max:2048'];

        return $reglas;
    }
}
