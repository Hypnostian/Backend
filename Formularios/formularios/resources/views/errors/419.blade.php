@extends('layouts.app')

@section('titulo', 'Sesión expirada')

@section('content')
    <section class="error-pagina">
        <p class="error-codigo">419</p>
        <h1 class="page-title">El formulario expiró</h1>
        <p class="page-subtitle">Por seguridad, la página estuvo abierta demasiado tiempo. Recárgala e inténtalo de nuevo.</p>
        <div class="error-acciones">
            <a href="{{ url()->previous() }}" class="btn btn-primary btn-lg">
                <x-icono nombre="flecha-izquierda" :tamano="20" /> Volver al formulario
            </a>
            <a href="{{ route('inicio') }}" class="btn btn-secondary btn-lg">Ir al inicio</a>
        </div>
    </section>
@endsection
