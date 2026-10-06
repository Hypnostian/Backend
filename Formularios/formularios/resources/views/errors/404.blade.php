@extends('layouts.app')

@section('titulo', 'Página no encontrada')

@section('content')
    <section class="error-pagina">
        <p class="error-codigo">404</p>
        <h1 class="page-title">Este plato no está en la carta</h1>
        <p class="page-subtitle">La página que buscas no existe o el plato fue retirado del menú.</p>
        <div class="error-acciones">
            <a href="{{ route('menu.index') }}" class="btn btn-primary btn-lg">
                <x-icono nombre="carta" :tamano="20" /> Ver la carta
            </a>
            <a href="{{ route('inicio') }}" class="btn btn-secondary btn-lg">Ir al inicio</a>
        </div>
    </section>
@endsection
