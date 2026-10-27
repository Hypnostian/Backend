<?php

// Mensajes de validación en español para las reglas que usan los formularios y la API del taller.
// Las reglas que no aparecen aquí se muestran con el texto en inglés de Laravel.

return [

    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'enum' => 'El valor seleccionado en :attribute no es válido.',
    'exists' => 'El valor seleccionado en :attribute no es válido.',
    'in' => 'El valor seleccionado en :attribute no es válido.',
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
        'doctor_id' => 'médico',
        'especialidad' => 'especialidad',
        'fecha_hora' => 'fecha y hora',
        'motivo' => 'motivo de la consulta',
        'estado' => 'estado',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
    ],

];
