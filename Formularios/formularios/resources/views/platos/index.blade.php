@extends('layouts.app')

@section('titulo', 'Administración de platos')

@section('content')
    <header class="page-header">
        <div>
            <p class="eyebrow">Gestión y mantenimiento</p>
            <h1 class="page-title">Platos del menú</h1>
            <p class="page-subtitle">
                {{ $platos->total() }} {{ $platos->total() === 1 ? 'plato registrado' : 'platos registrados' }} en la carta del restaurante.
            </p>
        </div>
        <a href="{{ route('platos.create') }}" class="btn btn-primary btn-lg">
            <x-icono nombre="mas" :tamano="20" /> Registrar plato
        </a>
    </header>

    @include('partials.alertas')

    <section class="card table-card" aria-label="Listado de platos">
        @if ($platos->isEmpty())
            <div class="empty">
                <span class="empty-icon"><x-icono nombre="plato" :tamano="30" /></span>
                <h2>Aún no hay platos registrados</h2>
                <p>Registra el primer plato para que aparezca en la carta del restaurante.</p>
                <a href="{{ route('platos.create') }}" class="btn btn-primary">
                    <x-icono nombre="mas" :tamano="18" /> Registrar el primer plato
                </a>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" class="col-id">ID</th>
                            <th scope="col" class="col-img">Imagen</th>
                            <th scope="col" class="col-nombre">Nombre</th>
                            <th scope="col" class="col-precio">Precio</th>
                            <th scope="col" class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($platos as $plato)
                            <tr>
                                <td class="col-id"><span class="id-badge">#{{ $plato->id }}</span></td>
                                <td class="col-img">
                                    <img src="{{ $plato->imagen_url }}" alt="{{ $plato->nombre }}" class="thumb" width="76" height="57" loading="lazy">
                                </td>
                                <td class="col-nombre">
                                    <span class="plato-nombre">{{ $plato->nombre }}</span>
                                    <span class="plato-meta">Actualizado {{ $plato->updated_at->diffForHumans() }}</span>
                                </td>
                                <td class="col-precio">{{ $plato->precio_formateado }}</td>
                                <td class="col-acciones">
                                    <div class="acciones">
                                        <a href="{{ route('platos.edit', $plato) }}" class="btn btn-secondary btn-sm">
                                            <x-icono nombre="editar" :tamano="16" /> Editar
                                        </a>
                                        <form action="{{ route('platos.destroy', $plato) }}" method="POST"
                                              data-confirm="¿Seguro que deseas eliminar «{{ $plato->nombre }}»? Dejará de aparecer en la carta.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-ghost-danger btn-sm">
                                                <x-icono nombre="eliminar" :tamano="16" /> Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($platos->hasPages())
                <div class="pagination-wrap">
                    <p class="pagination-info">Mostrando {{ $platos->firstItem() }}–{{ $platos->lastItem() }} de {{ $platos->total() }}</p>
                    {{ $platos->links('partials.paginacion') }}
                </div>
            @endif
        @endif
    </section>
@endsection
