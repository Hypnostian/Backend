@extends('layouts.app')

@section('titulo', 'Carta')
@section('descripcion', 'Carta del restaurante El Fogón: todos nuestros platos en orden alfabético.')

@section('content')
    <article class="carta">
        <header class="carta-header">
            <p class="carta-ornamento" aria-hidden="true"><x-icono nombre="cubiertos" :tamano="22" /></p>
            <h1 class="carta-titulo">Nuestra <em>carta</em></h1>
            <p class="carta-subtitulo">
                @if ($platos->isNotEmpty())
                    {{ $platos->count() }} {{ $platos->count() === 1 ? 'plato' : 'platos' }} en orden alfabético, preparados al momento.
                @else
                    Estamos preparando nuestra carta.
                @endif
            </p>
        </header>

        @forelse ($platosPorLetra as $letra => $platosDeLaLetra)
            <section class="carta-seccion" aria-labelledby="letra-{{ $loop->index }}">
                <h2 class="carta-letra" id="letra-{{ $loop->index }}"><span>{{ $letra }}</span></h2>

                <ul class="carta-lista">
                    @foreach ($platosDeLaLetra as $plato)
                        <li>
                            <a href="{{ route('menu.show', $plato) }}" class="carta-item">
                                <span class="carta-nombre">{{ $plato->nombre }}</span>
                                <span class="carta-puntos" aria-hidden="true"></span>
                                <span class="carta-precio">{{ $plato->precio_formateado }}</span>
                                <span class="carta-detalle">Ver detalle <x-icono nombre="flecha-derecha" :tamano="16" /></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <div class="empty">
                <span class="empty-icon"><x-icono nombre="plato" :tamano="30" /></span>
                <h2>La carta está vacía</h2>
                <p>Todavía no hay platos registrados. Vuelve pronto.</p>
                <a href="{{ route('platos.create') }}" class="btn btn-primary">
                    <x-icono nombre="mas" :tamano="18" /> Registrar un plato
                </a>
            </div>
        @endforelse

        @if ($platos->isNotEmpty())
            <p class="carta-pie">Precios en pesos colombianos (COP) · Impuestos incluidos</p>
        @endif
    </article>
@endsection
