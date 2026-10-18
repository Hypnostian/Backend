<?php

// Mensajes de validación en español para las reglas que usa la API.
// Las reglas que no aparecen aquí se muestran con el texto en inglés de Laravel.

return [

    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
        'file' => 'El campo :attribute no debe pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe superar los :max caracteres.',
    ],
    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'file' => 'El campo :attribute debe pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',

    'attributes' => [
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'titulo' => 'título',
        'descripcion' => 'descripción',
        'cupo_maximo' => 'cupo máximo',
    ],

];
