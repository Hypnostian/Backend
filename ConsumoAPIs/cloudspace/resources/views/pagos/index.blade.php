@extends('layouts.app')

@section('titulo', 'Pagos')
@section('descripcion', 'Pagos confirmados por Stripe y registrados mediante el webhook.')

@section('content')
    <div class="encabezado-pagina">
        <span class="etiqueta-superior"><x-icono nombre="rayo" :tamano="16" /> Webhook de Stripe</span>
        <h1>Historial de pagos</h1>
        <p class="lead">Cada vez que Stripe notifica un pago a <code>POST /stripe/webhook</code> se verifica la firma y el resultado queda registrado en MySQL.</p>
    </div>

    <div class="estadisticas">
        <div class="estadistica">
            <span>Pagos exitosos</span>
            <strong>{{ $exitosos }}</strong>
        </div>
        <div class="estadistica">
            <span>Pagos rechazados</span>
            <strong>{{ $fallidos }}</strong>
        </div>
        @forelse ($recaudo as $moneda => $total)
            <div class="estadistica">
                <span>Recaudo en {{ strtoupper($moneda) }}</span>
                <strong>{{ \App\Support\Dinero::formatear($total, $moneda) }}</strong>
            </div>
        @empty
            <div class="estadistica">
                <span>Recaudo</span>
                <strong>—</strong>
            </div>
        @endforelse
    </div>

    @if ($pagos->isEmpty())
        <div class="tarjeta vacio">
            <span class="icono-vacio"><x-icono nombre="base-datos" :tamano="26" /></span>
            <h2>Aún no hay pagos registrados</h2>
            <p>Cuando completes un pago en el checkout, Stripe lo notificará al webhook y aparecerá aquí con su estado.</p>
            <a href="{{ route('checkout.create') }}" class="btn btn-primario">
                <x-icono nombre="tarjeta" :tamano="18" /> Ir al checkout
            </a>
        </div>
    @else
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Concepto</th>
                        <th scope="col">Monto</th>
                        <th scope="col">Tarjeta</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Correo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pagos as $pago)
                        <tr>
                            <td>
                                {{ ($pago->pagado_at ?? $pago->created_at)->format('d/m/Y') }}
                                <small>{{ ($pago->pagado_at ?? $pago->created_at)->format('h:i a') }}</small>
                            </td>
                            <td>
                                {{ $pago->cliente_nombre ?? 'Sin nombre' }}
                                <small>{{ $pago->cliente_email ?? 'Sin correo' }}</small>
                            </td>
                            <td>
                                {{ $pago->concepto ?? 'Reserva de espacio' }}
                                <small class="mono">{{ $pago->payment_intent_id }}</small>
                            </td>
                            <td class="monto">{{ $pago->monto_formateado }}</td>
                            <td>{{ $pago->tarjeta ?? '—' }}</td>
                            <td>
                                @if ($pago->fueExitoso())
                                    <span class="insignia insignia-exito">Exitoso</span>
                                @else
                                    <span class="insignia insignia-error">Rechazado</span>
                                    @if ($pago->error)
                                        <small>{{ $pago->error }}</small>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if ($pago->correo_enviado_at)
                                    <span class="insignia insignia-exito">Enviado</span>
                                @elseif ($pago->fueExitoso())
                                    <span class="insignia insignia-pendiente">No enviado</span>
                                    <small><a href="{{ route('logs.index', ['filtro' => 'sendgrid']) }}">Ver logs</a></small>
                                @else
                                    <span class="insignia insignia-neutra">No aplica</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($pagos->hasPages())
            <nav class="paginacion" aria-label="Paginación de pagos">
                <span>Mostrando {{ $pagos->firstItem() }} a {{ $pagos->lastItem() }} de {{ $pagos->total() }} pagos</span>
                <div class="paginacion-enlaces">
                    @if ($pagos->onFirstPage())
                        <span class="btn btn-secundario" aria-disabled="true">Anterior</span>
                    @else
                        <a href="{{ $pagos->previousPageUrl() }}" class="btn btn-secundario" rel="prev">Anterior</a>
                    @endif
                    @if ($pagos->hasMorePages())
                        <a href="{{ $pagos->nextPageUrl() }}" class="btn btn-secundario" rel="next">Siguiente</a>
                    @else
                        <span class="btn btn-secundario" aria-disabled="true">Siguiente</span>
                    @endif
                </div>
            </nav>
        @endif
    @endif
@endsection
