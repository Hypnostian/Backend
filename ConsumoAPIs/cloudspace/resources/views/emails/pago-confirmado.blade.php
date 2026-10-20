@extends('emails.layout')

@php
    $filas = [
        'Concepto' => $pago->concepto ?? 'Reserva de espacio',
        'Monto' => $pago->monto_formateado,
        'Tarjeta' => $pago->tarjeta,
        'Fecha' => $pago->pagado_at?->locale('es')->translatedFormat('j \d\e F \d\e Y, h:i a'),
        'Referencia' => $pago->payment_intent_id,
    ];
@endphp

@section('contenido')
    <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#0d7552;">Pago confirmado</p>
    <h1 style="margin:0 0 12px;font-size:22px;line-height:1.3;color:#0f1b2d;">¡Gracias{{ $pago->cliente_nombre ? ', '.$pago->cliente_nombre : '' }}!</h1>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#22324a;">Recibimos el pago de tu reserva. Estos son los detalles de la transacción:</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #dbe3ef;border-radius:12px;font-size:14px;">
        @foreach (array_filter($filas) as $etiqueta => $valor)
            <tr>
                <td style="padding:12px 16px;color:#55657d;{{ $loop->last ? '' : 'border-bottom:1px solid #dbe3ef;' }}">{{ $etiqueta }}</td>
                <td style="padding:12px 16px;text-align:right;font-weight:700;color:#0f1b2d;{{ $loop->last ? 'font-family:Consolas,monospace;font-size:12px;font-weight:400;' : 'border-bottom:1px solid #dbe3ef;' }}">{{ $valor }}</td>
            </tr>
        @endforeach
    </table>

    <p style="margin:20px 0 0;font-size:14px;line-height:1.6;color:#55657d;">Presenta este correo en la recepción el día de tu reserva. ¡Te esperamos!</p>
@endsection
