@extends('layouts.app')

@php
    $codigo = $exception->getStatusCode();
    [$titulo, $mensaje] = match ($codigo) {
        403 => ['Acceso no permitido', $exception->getMessage() ?: 'No tienes permiso para realizar esta acción.'],
        404 => ['Página no encontrada', 'La dirección que buscas no existe.'],
        419 => ['La sesión expiró', 'Por seguridad el formulario venció. Vuelve atrás, recarga la página e inténtalo de nuevo.'],
        429 => ['Demasiadas solicitudes', 'Hiciste muchas solicitudes seguidas. Espera un minuto antes de volver a intentarlo.'],
        default => ['No pudimos procesar la solicitud', 'Revisa la dirección o vuelve al listado de citas.'],
    };
@endphp

@section('titulo', $titulo)

@section('content')
    <div class="card" style="max-width: 560px; margin: 0 auto;">
        <div class="card-body vacio">
            <strong>{{ $titulo }}</strong>
            <p>{{ $mensaje }} (Error {{ $codigo }})</p>
            <a href="{{ route('citas.index') }}" class="btn btn-primario">
                <x-icono nombre="lista" :tamano="18" /> Ir al listado de citas
            </a>
        </div>
    </div>
@endsection
