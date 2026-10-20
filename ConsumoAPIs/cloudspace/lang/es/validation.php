<?php

// Mensajes de validación en español para las reglas que usan los formularios del taller.
// Las reglas que no aparecen aquí se muestran con el texto en inglés de Laravel.

return [

    'decimal' => 'El campo :attribute admite :decimal cifras decimales.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'gt' => [
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
    ],
    'in' => 'El valor seleccionado en :attribute no es válido.',
    'max' => [
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
        'file' => 'El campo :attribute no debe pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe superar los :max caracteres.',
    ],
    'numeric' => 'El campo :attribute debe ser un número.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',

    'attributes' => [
        'monto' => 'monto',
        'moneda' => 'moneda',
        'nombre' => 'nombre del titular',
        'email' => 'correo electrónico',
        'concepto' => 'concepto',
        'destinatarios' => 'destinatarios',
        'asunto' => 'asunto',
        'mensaje' => 'mensaje',
    ],

];
