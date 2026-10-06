@extends('layouts.app')

@section('titulo', 'Contacto')

@section('content')
    <div class="contacto">
        <section class="contacto-intro">
            <p class="eyebrow">Estamos para escucharte</p>
            <h1 class="page-title">Formulario de contacto</h1>
            <p class="page-subtitle">Déjanos tus datos y cuéntanos cómo podemos ayudarte: reservas, eventos o sugerencias sobre la carta.</p>

            <ul class="contacto-datos">
                <li>
                    <span class="contacto-dato-icono"><x-icono nombre="ubicacion" :tamano="20" /></span>
                    <span><strong>Dirección</strong> Bogotá, Colombia</span>
                </li>
                <li>
                    <span class="contacto-dato-icono"><x-icono nombre="reloj" :tamano="20" /></span>
                    <span><strong>Horario</strong> Lunes a domingo, 12 m – 10 p. m.</span>
                </li>
                <li>
                    <span class="contacto-dato-icono"><x-icono nombre="correo" :tamano="20" /></span>
                    <span><strong>Respuesta</strong> En menos de 24 horas</span>
                </li>
            </ul>
        </section>

        <section class="card form-card contacto-card" aria-label="Formulario de contacto">
            @include('partials.alertas')

            @if (session('sent_data'))
                <div class="recibo" aria-labelledby="recibo-titulo">
                    <h2 id="recibo-titulo">Resumen del envío</h2>
                    <dl>
                        <div><dt>Nombre</dt><dd>{{ session('sent_data.nombre') }}</dd></div>
                        <div><dt>Correo</dt><dd>{{ session('sent_data.email') }}</dd></div>
                        <div><dt>Mensaje</dt><dd class="recibo-mensaje">{{ session('sent_data.mensaje') }}</dd></div>
                        <div>
                            <dt>Foto</dt>
                            <dd>
                                @if (session('sent_data.foto'))
                                    <img src="{{ asset('storage/'.session('sent_data.foto')) }}" alt="Foto adjunta" class="recibo-foto" width="160" height="120">
                                    <code>{{ session('sent_data.foto') }}</code>
                                @else
                                    Sin archivo
                                @endif
                            </dd>
                        </div>
                    </dl>
                    <a href="{{ route('contacto.create') }}" class="btn btn-secondary">
                        <x-icono nombre="enviar" :tamano="18" /> Enviar otro mensaje
                    </a>
                </div>
            @else
                <form action="{{ route('contacto.send') }}" method="POST" enctype="multipart/form-data" novalidate data-un-envio>
                    @csrf

                    <div class="field">
                        <label for="nombre" class="label">Nombre</label>
                        <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}"
                               class="input @error('nombre') is-invalid @enderror"
                               placeholder="Tu nombre completo" autocomplete="name" required>
                        @error('nombre')<p class="field-error"><x-icono nombre="alerta-circulo" :tamano="16" /> {{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="email" class="label">Correo electrónico</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="input @error('email') is-invalid @enderror"
                               placeholder="tu@correo.com" autocomplete="email" required>
                        @error('email')<p class="field-error"><x-icono nombre="alerta-circulo" :tamano="16" /> {{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="mensaje" class="label">Mensaje</label>
                        <textarea id="mensaje" name="mensaje" rows="5"
                                  class="textarea @error('mensaje') is-invalid @enderror"
                                  placeholder="Escribe aquí los detalles..." required>{{ old('mensaje') }}</textarea>
                        @error('mensaje')<p class="field-error"><x-icono nombre="alerta-circulo" :tamano="16" /> {{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="foto" class="label">Foto <span class="opcional">(opcional)</span></label>
                        <div class="dropzone dropzone-compacta @error('foto') is-invalid @enderror" data-dropzone>
                            <input type="file" id="foto" name="foto" class="dropzone-input" accept="image/*" aria-describedby="foto-ayuda">
                            <img class="dropzone-preview" alt="Vista previa de la foto" hidden data-dropzone-preview>
                            <div class="dropzone-content">
                                <span class="dropzone-icon"><x-icono nombre="imagen" :tamano="24" /></span>
                                <span class="dropzone-title">Adjunta una foto</span>
                                <span class="dropzone-text">Imagen de máximo 2 MB</span>
                            </div>
                        </div>
                        @error('foto')<p class="field-error"><x-icono nombre="alerta-circulo" :tamano="16" /> {{ $message }}</p>@enderror
                        <p class="field-hint" id="foto-ayuda" data-dropzone-info>Puedes adjuntar una imagen relacionada con tu mensaje.</p>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        Enviar mensaje <x-icono nombre="flecha-derecha" :tamano="18" />
                    </button>
                </form>
            @endif
        </section>
    </div>
@endsection
