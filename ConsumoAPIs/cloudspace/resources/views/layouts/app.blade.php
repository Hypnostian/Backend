<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('titulo')@yield('titulo') · @endif{{ config('app.name') }}</title>
    <meta name="description" content="@yield('descripcion', 'CloudSpace Solutions: reserva y pago en línea de espacios de coworking y salas de conferencias.')">
    <meta name="theme-color" content="#2856e6">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/cloudspace.css') }}?v={{ filemtime(public_path('css/cloudspace.css')) }}">
    @stack('head')
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('inicio') }}" class="brand" aria-label="{{ config('app.name') }}, ir al inicio">
                <span class="brand-icon"><x-icono nombre="nube" :tamano="22" /></span>
                <span class="brand-text">
                    <span class="brand-name">CloudSpace</span>
                    <span class="brand-tag">Solutions</span>
                </span>
            </a>

            <nav class="main-nav" aria-label="Navegación principal">
                <a href="{{ route('checkout.create') }}" @class(['nav-link', 'is-active' => request()->routeIs('checkout.*')]) @if (request()->routeIs('checkout.*')) aria-current="page" @endif>
                    <x-icono nombre="tarjeta" :tamano="18" /> Pagar
                </a>
                <a href="{{ route('pagos.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('pagos.*')]) @if (request()->routeIs('pagos.*')) aria-current="page" @endif>
                    <x-icono nombre="recibo" :tamano="18" /> Pagos
                </a>
                <a href="{{ route('email.create') }}" @class(['nav-link', 'is-active' => request()->routeIs('email.*')]) @if (request()->routeIs('email.*')) aria-current="page" @endif>
                    <x-icono nombre="correo" :tamano="18" /> Correos
                </a>
                <a href="{{ route('logs.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('logs.*')]) @if (request()->routeIs('logs.*')) aria-current="page" @endif>
                    <x-icono nombre="terminal" :tamano="18" /> Logs
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
            <p>&copy; {{ now()->year }} {{ config('app.name') }} · Coworking y salas de conferencias</p>
            <p>Taller Laravel · Consumo de APIs (Stripe y SendGrid)</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
