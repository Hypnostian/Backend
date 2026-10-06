<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ContactoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación del formulario de contacto.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mensaje' => ['required', 'string', 'max:5000'],
            'foto' => ['nullable', 'image', 'max:2048'],
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
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'foto.image' => 'La foto debe ser una imagen (JPG, PNG, GIF, BMP o WEBP).',
            'foto.max' => 'La foto no puede pesar más de 2 MB.',
            'foto.uploaded' => 'La foto no se pudo subir; verifica que no pese más de 2 MB.',
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
            'email' => 'correo electrónico',
            'mensaje' => 'mensaje',
            'foto' => 'foto',
        ];
    }
}
