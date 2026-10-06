@extends('layouts.app')

@section('content')
    <section class="hero">
        <div class="hero-texto">
            <p class="eyebrow">Restaurante · Cocina de autor</p>
            <h1 class="hero-titulo">Sabores de fogón, servidos <em>sin prisa</em>.</h1>
            <p class="hero-descripcion">
                Recetas de casa preparadas a fuego lento con ingredientes frescos de la región.
                Explora la carta o administra los platos del menú.
            </p>
            <div class="hero-acciones">
                <a href="{{ route('menu.index') }}" class="btn btn-primary btn-lg">
                    <x-icono nombre="carta" :tamano="20" /> Ver la carta
                </a>
                <a href="{{ route('platos.index') }}" class="btn btn-secondary btn-lg">
                    <x-icono nombre="ajustes" :tamano="20" /> Administrar platos
                </a>
            </div>
            <dl class="hero-datos">
                <div>
                    <dt>Platos en carta</dt>
                    <dd>{{ $totalPlatos }}</dd>
                </div>
                <div>
                    <dt>Horario</dt>
                    <dd>12 m – 10 p. m.</dd>
                </div>
                <div>
                    <dt>Cocina</dt>
                    <dd>A la leña</dd>
                </div>
            </dl>
        </div>

        <div class="hero-visual" aria-hidden="true">
            @if ($recientes->isNotEmpty())
                <div class="hero-foto hero-foto-principal">
                    <img src="{{ $recientes[0]->imagen_url }}" alt="" width="1200" height="900">
                </div>
                @isset($recientes[1])
                    <div class="hero-foto hero-foto-secundaria">
                        <img src="{{ $recientes[1]->imagen_url }}" alt="" width="1200" height="900">
                    </div>
                @endisset
                <div class="hero-etiqueta">
                    <span class="hero-etiqueta-icono"><x-icono nombre="llama" :tamano="18" /></span>
                    <span>
                        <strong>{{ $recientes[0]->nombre }}</strong>
                        <small>{{ $recientes[0]->precio_formateado }}</small>
                    </span>
                </div>
            @else
                <div class="hero-foto hero-foto-principal hero-foto-vacia">
                    <x-icono nombre="cubiertos" :tamano="72" />
                </div>
            @endif
        </div>
    </section>

    @if ($recientes->isNotEmpty())
        <section class="seccion" aria-labelledby="titulo-recientes">
            <header class="seccion-header">
                <div>
                    <p class="eyebrow">Recién salidos del fogón</p>
                    <h2 class="seccion-titulo" id="titulo-recientes">Lo último en nuestra carta</h2>
                </div>
                <a href="{{ route('menu.index') }}" class="enlace-flecha">
                    Ver la carta completa <x-icono nombre="flecha-derecha" :tamano="18" />
                </a>
            </header>

            <div class="tarjetas">
                @foreach ($recientes as $plato)
                    <a href="{{ route('menu.show', $plato) }}" class="tarjeta-plato">
                        <span class="tarjeta-plato-imagen">
                            <img src="{{ $plato->imagen_url }}" alt="{{ $plato->nombre }}" width="1200" height="900" loading="lazy">
                        </span>
                        <span class="tarjeta-plato-cuerpo">
                            <span class="tarjeta-plato-nombre">{{ $plato->nombre }}</span>
                            <span class="tarjeta-plato-precio">{{ $plato->precio_formateado }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="seccion" aria-labelledby="titulo-modulos">
        <header class="seccion-header">
            <div>
                <p class="eyebrow">Módulos</p>
                <h2 class="seccion-titulo" id="titulo-modulos">¿Qué quieres hacer hoy?</h2>
            </div>
        </header>

        <div class="modulos">
            <a href="{{ route('platos.create') }}" class="modulo">
                <span class="modulo-icono"><x-icono nombre="mas" :tamano="22" /></span>
                <h3>Registrar platos</h3>
                <p>Agrega platos nuevos con su precio, descripción e imagen.</p>
                <span class="modulo-enlace">Ir al formulario <x-icono nombre="flecha-derecha" :tamano="16" /></span>
            </a>
            <a href="{{ route('platos.index') }}" class="modulo">
                <span class="modulo-icono"><x-icono nombre="lista" :tamano="22" /></span>
                <h3>Gestionar el menú</h3>
                <p>Consulta, edita o elimina los platos guardados.</p>
                <span class="modulo-enlace">Ver el listado <x-icono nombre="flecha-derecha" :tamano="16" /></span>
            </a>
            <a href="{{ route('menu.index') }}" class="modulo">
                <span class="modulo-icono"><x-icono nombre="carta" :tamano="22" /></span>
                <h3>Carta del restaurante</h3>
                <p>La vista pública con los platos en orden alfabético.</p>
                <span class="modulo-enlace">Abrir la carta <x-icono nombre="flecha-derecha" :tamano="16" /></span>
            </a>
        </div>
    </section>
@endsection
