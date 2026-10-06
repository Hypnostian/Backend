@extends('layouts.app')

@section('titulo', 'Registrar plato')

@section('content')
    <nav class="breadcrumb" aria-label="Ruta de navegación">
        <a href="{{ route('platos.index') }}">Administración</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Registrar plato</span>
    </nav>

    <header class="page-header">
        <div>
            <p class="eyebrow">Registro de platos</p>
            <h1 class="page-title">Registrar un plato</h1>
            <p class="page-subtitle">Completa la información tal como aparecerá en la carta del restaurante.</p>
        </div>
        <a href="{{ route('platos.index') }}" class="btn btn-secondary">
            <x-icono nombre="lista" :tamano="18" /> Ver listado
        </a>
    </header>

    @include('partials.alertas', [
        'enlaces' => session('plato_id')
            ? ['Ver en la carta' => route('menu.show', session('plato_id')), 'Ir al listado' => route('platos.index')]
            : [],
    ])

    <form action="{{ route('platos.store') }}" method="POST" enctype="multipart/form-data" class="card form-card" novalidate data-un-envio>
        @csrf

        <div class="form-grid">
            <div class="form-col">
                <div class="field">
                    <label for="nombre" class="label">Nombre del plato</label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        value="{{ old('nombre') }}"
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
                            value="{{ old('precio') }}"
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
                    >{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                        <p class="field-error" id="descripcion-error"><x-icono nombre="alerta-circulo" :tamano="16" /> {{ $message }}</p>
                    @else
                        <p class="field-hint">Mínimo 10 caracteres.</p>
                    @enderror
                </div>
            </div>

            <div class="form-col">
                <div class="field">
                    <label for="imagen" class="label">Imagen del plato</label>
                    <div class="dropzone @error('imagen') is-invalid @enderror" data-dropzone>
                        <input
                            type="file"
                            id="imagen"
                            name="imagen"
                            class="dropzone-input"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required
                            aria-describedby="imagen-ayuda"
                        >
                        <img class="dropzone-preview" alt="Vista previa de la imagen seleccionada" hidden data-dropzone-preview>
                        <div class="dropzone-content">
                            <span class="dropzone-icon"><x-icono nombre="imagen" :tamano="26" /></span>
                            <span class="dropzone-title">Haz clic o arrastra una imagen</span>
                            <span class="dropzone-text">JPG, PNG o WEBP · máximo 2 MB</span>
                        </div>
                    </div>
                    @error('imagen')
                        <p class="field-error"><x-icono nombre="alerta-circulo" :tamano="16" /> {{ $message }}</p>
                    @enderror
                    <p class="field-hint" id="imagen-ayuda" data-dropzone-info>
                        Se guardará en <code>storage/app/public/platos/{{ now()->format('Y/m') }}</code>.
                    </p>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <x-icono nombre="check" :tamano="18" /> Guardar plato
            </button>
            <a href="{{ route('platos.index') }}" class="btn btn-secondary btn-lg">Cancelar</a>
        </div>
    </form>
@endsection
