<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Espacios de CloudSpace Solutions
    |--------------------------------------------------------------------------
    |
    | Catálogo que muestra la página de inicio. Cada espacio abre el checkout con
    | el concepto, el monto y la moneda ya diligenciados.
    |
    */

    'espacios' => [
        [
            'nombre' => 'Puesto flexible',
            'concepto' => 'Puesto flexible (1 día)',
            'descripcion' => 'Escritorio en la zona de coworking durante un día completo, con WiFi de alta velocidad y café.',
            'precio' => 45000,
            'moneda' => 'cop',
            'unidad' => 'por día',
            'icono' => 'portatil',
        ],
        [
            'nombre' => 'Sala de conferencias',
            'concepto' => 'Sala de conferencias (2 horas)',
            'descripcion' => 'Sala para 12 personas con pantalla, videoconferencia y tablero, en bloques de dos horas.',
            'precio' => 120000,
            'moneda' => 'cop',
            'unidad' => 'por 2 horas',
            'icono' => 'presentacion',
        ],
        [
            'nombre' => 'Oficina privada',
            'concepto' => 'Oficina privada (1 día)',
            'descripcion' => 'Oficina cerrada para equipos de hasta cuatro personas, con locker y acceso a las zonas comunes.',
            'precio' => 180000,
            'moneda' => 'cop',
            'unidad' => 'por día',
            'icono' => 'edificio',
        ],
    ],

];
