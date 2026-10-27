<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('titulo')@yield('titulo') · @endif{{ config('app.name') }}</title>
    <meta name="description" content="MediCitas: agendamiento y consulta de citas médicas.">
    <meta name="theme-color" content="#0f766e">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/medicitas.css') }}?v={{ filemtime(public_path('css/medicitas.css')) }}">
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('citas.index') }}" class="brand" aria-label="{{ config('app.name') }}, ir al listado de citas">
                <span class="brand-icon"><x-icono nombre="cruz" :tamano="20" /></span>
                <span>Medi<b>Citas</b></span>
            </a>

            <nav class="main-nav" aria-label="Navegación principal">
                <a href="{{ route('citas.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('citas.index')]) @if (request()->routeIs('citas.index')) aria-current="page" @endif>
                    <x-icono nombre="lista" :tamano="18" /> Citas
                </a>
                @if (! auth()->user()?->esMedico())
                    <a href="{{ route('citas.create') }}" @class(['nav-link', 'is-active' => request()->routeIs('citas.create')]) @if (request()->routeIs('citas.create')) aria-current="page" @endif>
                        <x-icono nombre="calendario" :tamano="18" /> Agendar cita
                    </a>
                @endif
            </nav>

            <div class="session">
                @auth
                    <div class="session-user">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span>{{ auth()->user()->rol->etiqueta() }}@if (auth()->user()->esMedico()) · {{ auth()->user()->especialidad->value }}@endif</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secundario">
                            <x-icono nombre="salir" :tamano="18" /> Salir
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-secundario">
                        <x-icono nombre="entrar" :tamano="18" /> Iniciar sesión
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main id="contenido" class="site-main">
        <div class="container">
            @if (session('exito'))
                <div class="alerta alerta-exito" role="status">
                    <x-icono nombre="check" />
                    <p>{{ session('exito') }}</p>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p>&copy; {{ now()->year }} Clínica {{ config('app.name') }} · Gestión de citas médicas</p>
            <p>Taller Laravel · Pruebas y Depuración</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
