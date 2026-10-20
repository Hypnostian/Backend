@extends('layouts.app')

@php
    $codigo = $exception->getStatusCode();
    [$titulo, $mensaje] = match ($codigo) {
        404 => ['Página no encontrada', 'La dirección que buscas no existe o el pago que intentas consultar no tiene una referencia válida.'],
        419 => ['La sesión expiró', 'Por seguridad el formulario venció. Vuelve atrás, recarga la página e inténtalo de nuevo.'],
        429 => ['Demasiadas solicitudes', 'Hiciste muchas solicitudes seguidas. Espera un minuto antes de volver a intentarlo.'],
        default => ['No pudimos procesar la solicitud', 'Revisa la dirección o vuelve al inicio.'],
    };
@endphp

@section('titulo', $titulo)

@section('content')
    <div class="resultado">
        <div class="resultado-icono is-pendiente"><x-icono nombre="alerta" :tamano="38" /></div>
        <h1>{{ $titulo }}</h1>
        <p>{{ $mensaje }} <span class="texto-suave">(Error {{ $codigo }})</span></p>
        <div class="acciones">
            <a href="{{ route('inicio') }}" class="btn btn-primario">
                <x-icono nombre="inicio" :tamano="18" /> Ir al inicio
            </a>
        </div>
    </div>
@endsection
