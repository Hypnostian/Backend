@extends('layouts.app')

@section('titulo', $plato->nombre)
@section('descripcion', \Illuminate\Support\Str::limit($plato->descripcion, 150))

@section('content')
    <nav class="breadcrumb" aria-label="Ruta de navegación">
        <a href="{{ route('menu.index') }}">Carta</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ $plato->nombre }}</span>
    </nav>

    <article class="detalle">
        <figure class="detalle-imagen">
            <img src="{{ $plato->imagen_url }}" alt="Fotografía del plato {{ $plato->nombre }}" width="1200" height="900">
        </figure>

        <div class="detalle-info">
            <p class="eyebrow">De nuestra carta</p>
            <h1 class="detalle-nombre">{{ $plato->nombre }}</h1>

            <p class="detalle-precio">
                <span>Precio</span>
                <strong>{{ $plato->precio_formateado }}</strong>
            </p>

            <div class="detalle-descripcion">{{ $plato->descripcion }}</div>

            <div class="detalle-acciones">
                <a href="{{ route('menu.index') }}" class="btn btn-dark btn-lg">
                    <x-icono nombre="flecha-izquierda" :tamano="18" /> Volver al menú
                </a>
            </div>
        </div>
    </article>
@endsection
