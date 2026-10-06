@extends('layouts.app')

@section('titulo', 'Editar '.$plato->nombre)

@section('content')
    <nav class="breadcrumb" aria-label="Ruta de navegación">
        <a href="{{ route('platos.index') }}">Administración</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Editar plato #{{ $plato->id }}</span>
    </nav>

    <header class="page-header">
        <div>
            <p class="eyebrow">Mantenimiento de platos</p>
            <h1 class="page-title">Editar «{{ $plato->nombre }}»</h1>
            <p class="page-subtitle">Registrado el {{ $plato->created_at->translatedFormat('j \d\e F \d\e Y') }} · última actualización {{ $plato->updated_at->diffForHumans() }}.</p>
        </div>
        <a href="{{ route('menu.show', $plato) }}" class="btn btn-secondary">
            <x-icono nombre="carta" :tamano="18" /> Ver en la carta
        </a>
    </header>

    @include('partials.alertas')

    <form action="{{ route('platos.update', $plato) }}" method="POST" enctype="multipart/form-data" class="card form-card" novalidate data-un-envio>
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="form-col">
                <div class="field">
                    <label for="nombre" class="label">Nombre del plato</label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        value="{{ old('nombre', $plato->nombre) }}"
                        class="input @error('nombre') is-invalid @enderror"
                        placeholder="Ej. Hamburguesa Artesanal"
                        maxlength="100"
                        autocomplete="off"
                        required
                        @error('nombre') aria-invalid="true" aria-describedby="nombre-error" @enderror
                    >
                    @error('nombre')
                        <p class="field-error" id="nombre-error"><x-icono nombre="alerta-circulo" :tamano="16" /> {{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="precio" class="label">Precio de venta</label>
                    <div class="input-group">
                        <span class="input-prefix" aria-hidden="true">$</span>
                        <input
                            type="number"
                            id="precio"
                            name="precio"
                            value="{{ old('precio', $plato->precio) }}"
                            class="input @error('precio') is-invalid @enderror"
                            placeholder="28500.00"
                            min="1000"
                            step="0.01"
                            inputmode="decimal"
                            required
                            @error('precio') aria-invalid="true" aria-describedby="precio-error" @enderror
                        >
                    </div>
                    @error('precio')
                        <p class="field-error" id="precio-error"><x-icono nombre="alerta-circulo" :tamano="16" /> {{ $message }}</p>
                    @else
                        <p class="field-hint">Mínimo $ 1.000. Usa punto para los decimales.</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="descripcion" class="label">Descripción</label>
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="7"
                        class="textarea @error('descripcion') is-invalid @enderror"
                        placeholder="Ingredientes, preparación, acompañamientos..."
                        required
                        @error('descripcion') aria-invalid="true" aria-describedby="descripcion-error" @enderror
                    >{{ old('descripcion', $plato->descripcion) }}</textarea>
                    @error('descripcion')
                        <p class="field-error" id="descripcion-error"><x-icono nombre="alerta-circulo" :tamano="16" /> {{ $message }}</p>
                    @else
                        <p class="field-hint">Mínimo 10 caracteres.</p>
                    @enderror
                </div>
            </div>

            <div class="form-col">
                <figure class="imagen-actual" data-imagen-actual>
                    <img src="{{ $plato->imagen_url }}" alt="Imagen actual de {{ $plato->nombre }}" width="640" height="480">
                    <figcaption>
                        <span class="imagen-actual-titulo">Imagen actual</span>
                        <code>{{ $plato->imagen }}</code>
                    </figcaption>
                </figure>

                <div class="field">
                    <label for="imagen" class="label">Reemplazar imagen <span class="opcional">(opcional)</span></label>
                    <div class="dropzone dropzone-compacta @error('imagen') is-invalid @enderror" data-dropzone>
                        <input
                            type="file"
                            id="imagen"
                            name="imagen"
                            class="dropzone-input"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            aria-describedby="imagen-ayuda"
                        >
                        <img class="dropzone-preview" alt="Vista previa de la nueva imagen" hidden data-dropzone-preview>
                        <div class="dropzone-content">
                            <span class="dropzone-icon"><x-icono nombre="imagen" :tamano="24" /></span>
                            <span class="dropzone-title">Selecciona una nueva imagen</span>
                            <span class="dropzone-text">JPG, PNG o WEBP · máximo 2 MB</span>
                        </div>
                    </div>
                    @error('imagen')
                        <p class="field-error"><x-icono nombre="alerta-circulo" :tamano="16" /> {{ $message }}</p>
                    @enderror
                    <p class="field-hint" id="imagen-ayuda" data-dropzone-info>
                        Si no seleccionas una imagen nueva, se conserva la actual.
                    </p>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <x-icono nombre="check" :tamano="18" /> Guardar cambios
            </button>
            <a href="{{ route('platos.index') }}" class="btn btn-secondary btn-lg">Cancelar</a>
        </div>
    </form>
@endsection
