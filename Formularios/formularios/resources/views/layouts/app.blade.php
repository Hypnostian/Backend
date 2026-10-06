<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('titulo')@yield('titulo') · @endif{{ config('app.name') }}</title>
    <meta name="description" content="@yield('descripcion', 'Restaurante El Fogón: carta, administración de platos y contacto.')">
    <meta name="theme-color" content="#f7f1e6">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,400;1,9..144,500&family=Instrument+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/restaurante.css') }}?v={{ filemtime(public_path('css/restaurante.css')) }}">
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('inicio') }}" class="brand" aria-label="{{ config('app.name') }}, ir al inicio">
                <span class="brand-icon"><x-icono nombre="llama" :tamano="22" /></span>
                <span class="brand-text">
                    <span class="brand-name">{{ config('app.name') }}</span>
                    <span class="brand-tag">Cocina de fuego lento</span>
                </span>
            </a>

            <nav class="main-nav" aria-label="Navegación principal">
                <a href="{{ route('menu.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('menu.*')]) @if (request()->routeIs('menu.*')) aria-current="page" @endif>
                    <x-icono nombre="carta" :tamano="18" /> Carta
                </a>
                <a href="{{ route('platos.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('platos.*')]) @if (request()->routeIs('platos.*')) aria-current="page" @endif>
                    <x-icono nombre="ajustes" :tamano="18" /> Administración
                </a>
                <a href="{{ route('contacto.create') }}" @class(['nav-link', 'is-active' => request()->routeIs('contacto.*')]) @if (request()->routeIs('contacto.*')) aria-current="page" @endif>
                    <x-icono nombre="correo" :tamano="18" /> Contacto
                </a>
            </nav>
        </div>
    </header>

    <main id="contenido" class="site-main">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p>&copy; {{ now()->year }} {{ config('app.name') }} · Cocina de fuego lento</p>
            <p>Taller Laravel · Formularios</p>
        </div>
    </footer>

    {{-- Ventana de confirmación usada por los formularios con data-confirm (p. ej. eliminar). --}}
    <dialog class="dialog" id="dialogo-confirmacion" aria-labelledby="dialogo-titulo" aria-describedby="dialogo-mensaje">
        <form method="dialog" class="dialog-body">
            <span class="dialog-icon"><x-icono nombre="alerta" :tamano="26" /></span>
            <h2 class="dialog-title" id="dialogo-titulo">¿Confirmar eliminación?</h2>
            <p class="dialog-text" id="dialogo-mensaje"></p>
            <div class="dialog-actions">
                <button type="submit" value="cancelar" class="btn btn-secondary" autofocus>Cancelar</button>
                <button type="submit" value="confirmar" class="btn btn-danger">
                    <x-icono nombre="eliminar" :tamano="18" /> Sí, eliminar
                </button>
            </div>
        </form>
    </dialog>

    <script src="{{ asset('js/restaurante.js') }}?v={{ filemtime(public_path('js/restaurante.js')) }}" defer></script>
</body>
</html>
