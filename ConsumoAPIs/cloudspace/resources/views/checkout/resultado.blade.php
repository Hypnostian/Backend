@extends('layouts.app')

@section('titulo', 'Resultado del pago')

@php
    $estado = $resumen['estado'] ?? ($pago?->fueExitoso() ? 'succeeded' : null);
    $aprobado = $estado === 'succeeded';
    $procesando = $estado === 'processing';
    $esperandoWebhook = $aprobado && ! $pago;
    $nombre = $resumen['cliente_nombre'] ?? $pago?->cliente_nombre;
@endphp

@section('content')
    <div class="resultado">
        @if ($aprobado)
            <div class="resultado-icono"><x-icono nombre="check" :tamano="38" /></div>
            <h1>¡Pago realizado{{ $nombre ? ', '.\Illuminate\Support\Str::before($nombre, ' ') : '' }}!</h1>
            <p>
                Stripe aprobó el pago de tu reserva.
                @if ($pago?->correo_enviado_at)
                    Te enviamos la confirmación a {{ $pago->cliente_email }}.
                @elseif ($pago)
                    El pago quedó registrado, pero no pudimos enviar la confirmación por correo.
                @else
                    Te enviaremos la confirmación por correo.
                @endif
            </p>
        @elseif ($procesando)
            <div class="resultado-icono is-pendiente"><x-icono nombre="reloj" :tamano="38" /></div>
            <h1>Pago en proceso</h1>
            <p>Stripe todavía está procesando el pago. Te avisaremos por correo cuando se confirme.</p>
        @elseif ($resumen)
            <div class="resultado-icono is-error"><x-icono nombre="error" :tamano="38" /></div>
            <h1>El pago no se completó</h1>
            <p>{{ $resumen['error'] ?? 'Stripe no registró un cobro exitoso para esta referencia.' }}</p>
        @else
            <div class="resultado-icono is-error"><x-icono nombre="alerta" :tamano="38" /></div>
            <h1>No pudimos consultar el pago</h1>
            <p>{{ $error }}</p>
        @endif

        @if ($resumen || $pago)
            <section class="tarjeta" aria-labelledby="titulo-detalle">
                <h2 class="tarjeta-titulo" id="titulo-detalle">
                    <span class="icono-caja"><x-icono nombre="recibo" /></span> Detalle del pago
                </h2>
                <dl class="resumen">
                    <div><dt>Referencia</dt><dd class="mono">{{ $id }}</dd></div>
                    <div><dt>Concepto</dt><dd>{{ $resumen['concepto'] ?? $pago?->concepto ?? 'Reserva de espacio' }}</dd></div>
                    <div>
                        <dt>Monto</dt>
                        <dd>{{ $resumen ? \App\Support\Dinero::formatear($resumen['monto'], $resumen['moneda']) : $pago->monto_formateado }}</dd>
                    </div>
                    @if ($tarjeta = $resumen['tarjeta'] ?? $pago?->tarjeta)
                        <div><dt>Tarjeta</dt><dd>{{ $tarjeta }}</dd></div>
                    @endif
                    @if ($resumen)
                        <div>
                            <dt>Estado en Stripe</dt>
                            <dd>
                                <span @class(['insignia', 'insignia-exito' => $aprobado, 'insignia-pendiente' => $procesando, 'insignia-error' => ! $aprobado && ! $procesando])>
                                    {{ $resumen['estado_texto'] }}
                                </span>
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt>Registro (webhook)</dt>
                        <dd>
                            @if ($pago)
                                <span @class(['insignia', 'insignia-exito' => $pago->fueExitoso(), 'insignia-error' => ! $pago->fueExitoso()])>
                                    {{ $pago->fueExitoso() ? 'Registrado' : 'Rechazo registrado' }}
                                </span>
                            @elseif ($aprobado)
                                <span class="insignia insignia-pendiente">Esperando a Stripe</span>
                            @else
                                <span class="insignia insignia-neutra">Sin registro</span>
                            @endif
                        </dd>
                    </div>
                    @if ($pago?->fueExitoso())
                        <div>
                            <dt>Correo de confirmación</dt>
                            <dd>
                                @if ($pago->correo_enviado_at)
                                    <span class="insignia insignia-exito">Enviado</span>
                                    <small class="texto-suave">{{ $pago->cliente_email }}</small>
                                @else
                                    <span class="insignia insignia-pendiente">No enviado</span>
                                    <small><a href="{{ route('logs.index', ['filtro' => 'sendgrid']) }}">Ver el error en Logs</a></small>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>

                @if ($esperandoWebhook)
                    <div class="alerta alerta-info alerta-separada" id="aviso-webhook">
                        <x-icono nombre="refrescar" />
                        <div>
                            Stripe está notificando el pago al webhook; esta página se actualiza sola.
                            Si no cambia, revisa que el contenedor <code>stripe-cli</code> esté encendido y que <code>STRIPE_WEBHOOK_SECRET</code> esté configurado.
                        </div>
                    </div>
                @endif
            </section>
        @endif

        <div class="acciones">
            <a href="{{ route('pagos.index') }}" class="btn btn-secundario">
                <x-icono nombre="recibo" :tamano="18" /> Ver historial de pagos
            </a>
            <a href="{{ route('checkout.create') }}" class="btn btn-primario">
                <x-icono nombre="tarjeta" :tamano="18" /> {{ $aprobado ? 'Hacer otro pago' : 'Intentar de nuevo' }}
            </a>
        </div>
    </div>
@endsection

@if ($esperandoWebhook)
    @push('scripts')
        <script>
            // El webhook suele llegar en uno o dos segundos: se recarga la página hasta 10 veces
            setTimeout(() => {
                const url = new URL(window.location.href);
                const intento = Number(url.searchParams.get('espera') || 0);

                if (intento < 10) {
                    url.searchParams.set('espera', intento + 1);
                    window.location.replace(url);
                }
            }, 2500);
        </script>
    @endpush
@endif
