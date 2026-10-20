@extends('layouts.app')

@section('titulo', 'Logs')
@section('descripcion', 'Eventos y errores de las integraciones con Stripe, el webhook y SendGrid.')

@section('content')
    <div class="encabezado-pagina">
        <span class="etiqueta-superior"><x-icono nombre="terminal" :tamano="16" /> Monitoreo</span>
        <h1>Logs de integraciones</h1>
        <p class="lead">Todo lo que pasa con Stripe, el webhook y SendGrid: cobros creados, eventos recibidos, correos enviados y los errores que devuelven las APIs. También se guardan en <code>storage/logs/integraciones-AAAA-MM-DD.log</code>.</p>
    </div>

    <div class="estadisticas">
        <div class="estadistica">
            <span>Errores</span>
            <strong @class(['texto-error' => $errores > 0])>{{ $errores }}</strong>
        </div>
        <div class="estadistica">
            <span>Advertencias</span>
            <strong @class(['texto-aviso' => $advertencias > 0])>{{ $advertencias }}</strong>
        </div>
        <div class="estadistica">
            <span>Registros en total</span>
            <strong>{{ $total }}</strong>
        </div>
    </div>

    <nav class="filtros" aria-label="Filtrar logs">
        @foreach ($filtros as $clave => $nombre)
            <a href="{{ route('logs.index', $clave === 'todos' ? [] : ['filtro' => $clave]) }}" @class(['filtro', 'is-activo' => $filtro === $clave]) @if ($filtro === $clave) aria-current="page" @endif>
                {{ $nombre }}
            </a>
        @endforeach
    </nav>

    @if ($registros->isEmpty())
        <div class="tarjeta vacio">
            <span class="icono-vacio"><x-icono nombre="terminal" :tamano="26" /></span>
            <h2>No hay registros {{ $filtro === 'todos' ? 'todavía' : 'con este filtro' }}</h2>
            <p>Cuando se cree un pago, llegue un evento al webhook o se envíe un correo, el resultado aparecerá aquí, incluidos los errores de las APIs.</p>
        </div>
    @else
        <div class="tabla-contenedor">
            <table class="tabla tabla-logs">
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Nivel</th>
                        <th scope="col">Servicio</th>
                        <th scope="col">Evento</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registros as $registro)
                        <tr @class(['fila-error' => $registro->esError(), 'fila-aviso' => $registro->esAdvertencia()])>
                            <td>
                                {{ $registro->created_at->format('d/m/Y') }}
                                <small>{{ $registro->created_at->format('h:i:s a') }}</small>
                            </td>
                            <td>
                                <span @class([
                                    'insignia',
                                    'insignia-error' => $registro->esError(),
                                    'insignia-pendiente' => $registro->esAdvertencia(),
                                    'insignia-neutra' => ! $registro->esError() && ! $registro->esAdvertencia(),
                                ])>{{ $registro->nombreNivel() }}</span>
                            </td>
                            <td>{{ $registro->nombreServicio() }}</td>
                            <td>
                                <strong class="log-mensaje">{{ $registro->mensaje }}</strong>
                                @if ($detalles = $registro->detalles())
                                    <dl class="log-detalles">
                                        @foreach ($detalles as $etiqueta => $valor)
                                            <div>
                                                <dt>{{ $etiqueta }}</dt>
                                                <dd>{{ $valor }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($registros->hasPages())
            <nav class="paginacion" aria-label="Paginación de logs">
                <span>Mostrando {{ $registros->firstItem() }} a {{ $registros->lastItem() }} de {{ $registros->total() }} registros</span>
                <div class="paginacion-enlaces">
                    @if ($registros->onFirstPage())
                        <span class="btn btn-secundario" aria-disabled="true">Anterior</span>
                    @else
                        <a href="{{ $registros->previousPageUrl() }}" class="btn btn-secundario" rel="prev">Anterior</a>
                    @endif
                    @if ($registros->hasMorePages())
                        <a href="{{ $registros->nextPageUrl() }}" class="btn btn-secundario" rel="next">Siguiente</a>
                    @else
                        <span class="btn btn-secundario" aria-disabled="true">Siguiente</span>
                    @endif
                </div>
            </nav>
        @endif
    @endif
@endsection
