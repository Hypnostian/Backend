@extends('layouts.app')

@section('titulo', 'Correos')
@section('descripcion', 'Envío de correos de prueba con la API REST de SendGrid.')

@section('content')
    <div class="encabezado-pagina">
        <span class="etiqueta-superior"><x-icono nombre="correo" :tamano="16" /> SendGrid Mail</span>
        <h1>Envío de correos</h1>
        <p class="lead">Formulario con datos de prueba para enviar correos transaccionales a uno o varios destinatarios mediante la API REST v3 de SendGrid.</p>
    </div>

    <div class="grid-correo">
        <section class="tarjeta" aria-labelledby="titulo-formulario">
            <h2 class="tarjeta-titulo" id="titulo-formulario">
                <span class="icono-caja"><x-icono nombre="enviar" /></span> Nuevo correo
            </h2>

            @if ($enviado = session('enviado'))
                <div class="alerta alerta-exito" role="status">
                    <x-icono nombre="check" />
                    <div>
                        @if ($enviado['sandbox'])
                            <strong>SendGrid validó el correo en modo sandbox (no se entregó).</strong>
                        @else
                            <strong>Correo enviado con éxito a través de la API de SendGrid.</strong>
                        @endif
                        Destinatarios: {{ implode(', ', $enviado['destinatarios']) }}.
                        @unless ($enviado['sandbox'])
                            <br>Identificador del mensaje: <span class="mono">{{ $enviado['id'] }}</span>
                        @endunless
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="alerta alerta-error" role="alert">
                    <x-icono nombre="error" />
                    <div>
                        <strong>No se pudo enviar el correo.</strong> {{ session('error') }}
                        <a href="{{ route('logs.index', ['filtro' => 'sendgrid']) }}">Ver el registro en Logs</a>
                    </div>
                </div>
            @endif

            @unless ($configurado)
                <div class="alerta alerta-aviso" role="alert">
                    <x-icono nombre="alerta" />
                    <div>
                        <strong>SendGrid no está configurado.</strong>
                        Agrega <code>SENDGRID_API_KEY</code> y <code>SENDGRID_FROM_ADDRESS</code> al archivo <code>.env</code>.
                    </div>
                </div>
            @endunless

            <form action="{{ route('email.store') }}" method="POST" novalidate>
                @csrf

                <div class="campo">
                    <label for="destinatarios">Destinatarios</label>
                    <textarea class="control @error('destinatarios') is-invalid @enderror" id="destinatarios" name="destinatarios" rows="2" required placeholder="ana@ejemplo.com, carlos@ejemplo.com" @error('destinatarios') aria-invalid="true" aria-describedby="error-destinatarios" @enderror>{{ old('destinatarios') }}</textarea>
                    <p class="ayuda">Separa los correos con comas o saltos de línea (máximo {{ $maximo }}). Cada destinatario recibe su propia copia.</p>
                    @error('destinatarios')
                        <p class="error-campo" id="error-destinatarios">{{ $message }}</p>
                    @enderror
                </div>

                <div class="campo">
                    <label for="asunto">Asunto</label>
                    <input class="control @error('asunto') is-invalid @enderror" type="text" id="asunto" name="asunto" maxlength="150" required value="{{ old('asunto', $prueba['asunto']) }}">
                    @error('asunto')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <div class="campo">
                    <label for="mensaje">Mensaje</label>
                    <textarea class="control @error('mensaje') is-invalid @enderror" id="mensaje" name="mensaje" rows="8" maxlength="5000" required>{{ old('mensaje', $prueba['mensaje']) }}</textarea>
                    @error('mensaje')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primario btn-bloque">
                    <x-icono nombre="enviar" :tamano="18" /> Enviar con SendGrid
                </button>
            </form>
        </section>

        <aside>
            <section class="tarjeta" aria-labelledby="titulo-configuracion">
                <h2 class="tarjeta-titulo" id="titulo-configuracion">
                    <span class="icono-caja"><x-icono nombre="info" /></span> Configuración
                </h2>
                <div class="dato-config">
                    <span>API</span>
                    <strong class="mono">POST /v3/mail/send</strong>
                </div>
                <div class="dato-config">
                    <span>Remitente</span>
                    <strong>{{ $remitente ?: 'Sin configurar' }}</strong>
                </div>
                <div class="dato-config">
                    <span>Nombre</span>
                    <strong>{{ $nombreRemitente ?: '—' }}</strong>
                </div>
                <div class="dato-config">
                    <span>Modo</span>
                    <strong>{{ $sandbox ? 'Sandbox (no entrega)' : 'Envío real' }}</strong>
                </div>
            </section>

            <section class="tarjeta" aria-labelledby="titulo-flujo">
                <h2 class="tarjeta-titulo" id="titulo-flujo">
                    <span class="icono-caja"><x-icono nombre="rayo" /></span> ¿Cómo funciona?
                </h2>
                <ol class="lista-pasos">
                    <li><code>EmailController</code> valida el formulario.</li>
                    <li><code>SendGridService</code> arma el JSON y lo envía con la API key en la cabecera <code>Authorization: Bearer</code>.</li>
                    <li>SendGrid responde <strong>202 Accepted</strong> y entrega el correo; si hay un error se muestra el motivo.</li>
                    <li>Los pagos confirmados usan el mismo servicio para el comprobante del cliente.</li>
                </ol>
            </section>
        </aside>
    </div>
@endsection
