@props(['nombre', 'tamano' => 20])

<svg {{ $attributes->merge(['class' => 'icono']) }} width="{{ $tamano }}" height="{{ $tamano }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($nombre)
        @case('llama')
            <path d="M12 2.8c.4 3 4.9 5.2 4.9 10.3A4.9 4.9 0 0 1 12 18a4.9 4.9 0 0 1-4.9-4.9c0-2.5 1.3-4.1 2.7-5.2.1 1.8.9 2.9 1.9 3.4-.3-3.1 0-5.9.3-8.5z" fill="currentColor" stroke="none" />
            <path d="M6.5 21h11" />
            @break
        @case('mas')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('editar')
            <path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3z" />
            <path d="M13.5 6.5l3 3" />
            @break
        @case('eliminar')
            <path d="M4 7h16" />
            <path d="M10 11v6M14 11v6" />
            <path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12" />
            <path d="M9 7V4h6v3" />
            @break
        @case('flecha-izquierda')
            <path d="M19 12H5M11 18l-6-6 6-6" />
            @break
        @case('flecha-derecha')
            <path d="M5 12h14M13 6l6 6-6 6" />
            @break
        @case('imagen')
            <rect x="3" y="4" width="18" height="16" rx="3" />
            <circle cx="9" cy="10" r="2" />
            <path d="M21 16l-5-5-9 9" />
            @break
        @case('check')
            <circle cx="12" cy="12" r="9" />
            <path d="M8 12.5l2.5 2.5L16 9.5" />
            @break
        @case('alerta')
            <path d="M10.3 4.2L2.6 17.5A2 2 0 0 0 4.3 20.5h15.4a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0z" />
            <path d="M12 9.5v4M12 17h.01" />
            @break
        @case('alerta-circulo')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7.5v5M12 16h.01" />
            @break
        @case('cubiertos')
            <path d="M7 3v7a2 2 0 0 1-2 2M7 10a2 2 0 0 0 2 2M5 3v5M9 3v5M7 12v9" />
            <path d="M17 21V3c-2 1.2-3 3.6-3 6.5V14h3" />
            @break
        @case('carta')
            <path d="M12 6.5C10 5 7 4.5 4 5v14c3-.5 6 0 8 1.5 2-1.5 5-2 8-1.5V5c-3-.5-6 0-8 1.5z" />
            <path d="M12 6.5v14" />
            @break
        @case('ajustes')
            <path d="M4 7h9M17 7h3M4 17h3M11 17h9" />
            <circle cx="15" cy="7" r="2" />
            <circle cx="9" cy="17" r="2" />
            @break
        @case('lista')
            <path d="M9 6h11M9 12h11M9 18h11" />
            <path d="M4.5 6h.01M4.5 12h.01M4.5 18h.01" />
            @break
        @case('correo')
            <rect x="3" y="5" width="18" height="14" rx="3" />
            <path d="M4 7.5l8 5.5 8-5.5" />
            @break
        @case('ubicacion')
            <path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z" />
            <circle cx="12" cy="9" r="2.5" />
            @break
        @case('telefono')
            <path d="M5 4h3.5l1.8 4.5-2.3 1.4a11 11 0 0 0 5.6 5.6l1.4-2.3L19.5 15v3.5a2 2 0 0 1-2.2 2A16 16 0 0 1 3 6.2 2 2 0 0 1 5 4z" />
            @break
        @case('reloj')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
            @break
        @case('plato')
            <circle cx="12" cy="12" r="9" />
            <circle cx="12" cy="12" r="5" />
            @break
        @case('enviar')
            <path d="M21 3L10 14" />
            <path d="M21 3l-7 18-4-7-7-4 18-7z" />
            @break
        @case('cerrar')
            <path d="M6 6l12 12M18 6L6 18" />
            @break
    @endswitch
</svg>
