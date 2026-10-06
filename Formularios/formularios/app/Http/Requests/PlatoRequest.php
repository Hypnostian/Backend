<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlatoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para registrar un plato.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Único en la tabla "platos"; los platos eliminados (soft delete) no bloquean el nombre.
            'nombre' => ['required', 'string', 'min:3', 'max:100', Rule::unique('platos', 'nombre')->withoutTrashed()],
            // max y decimal evitan valores que no caben en la columna decimal(10, 2).
            'precio' => ['required', 'numeric', 'min:1000', 'max:99999999.99', 'decimal:0,2'],
            'descripcion' => ['required', 'string', 'min:10'],
            'imagen' => ['required', 'image', 'mimes:jpg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser un texto.',

            'nombre.min' => 'El nombre debe tener al menos :min caracteres.',
            'nombre.max' => 'El nombre no puede tener más de :max caracteres.',
            'nombre.unique' => 'Ya existe un plato registrado con este nombre.',

            'precio.numeric' => 'El precio debe ser un número.',
            'precio.min' => 'El precio mínimo es de $ 1.000.',
            'precio.max' => 'El precio no puede superar $ 99.999.999,99.',
            'precio.decimal' => 'El precio puede tener máximo 2 decimales.',

            'descripcion.min' => 'La descripción debe tener al menos :min caracteres.',

            'imagen.required' => 'Debes seleccionar una imagen para el plato.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe estar en formato JPG, PNG o WEBP.',
            'imagen.max' => 'La imagen no puede pesar más de 2 MB.',
            'imagen.uploaded' => 'La imagen no se pudo subir; verifica que no pese más de 2 MB.',
        ];
    }

    /**
     * Nombres legibles de los campos para los mensajes.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'precio' => 'precio',
            'descripcion' => 'descripción',
            'imagen' => 'imagen',
        ];
    }
}
