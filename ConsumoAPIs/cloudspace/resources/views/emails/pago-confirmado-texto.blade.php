¡Gracias{!! $pago->cliente_nombre ? ', '.$pago->cliente_nombre : '' !!}!

Recibimos el pago de tu reserva en {!! config('app.name') !!}.

Concepto: {!! $pago->concepto ?? 'Reserva de espacio' !!}
Monto: {!! $pago->monto_formateado !!}
@if ($pago->tarjeta)
Tarjeta: {!! $pago->tarjeta !!}
@endif
@if ($pago->pagado_at)
Fecha: {!! $pago->pagado_at->locale('es')->translatedFormat('j \d\e F \d\e Y, h:i a') !!}
@endif
Referencia: {!! $pago->payment_intent_id !!}

Presenta este correo en la recepción el día de tu reserva.
