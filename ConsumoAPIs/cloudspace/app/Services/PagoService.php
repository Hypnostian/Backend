<?php

namespace App\Services;

use App\Events\PagoConfirmado;
use App\Logging\EscribeLogs;
use App\Models\Pago;
use Illuminate\Support\Carbon;
use Stripe\Event;
use Stripe\PaymentIntent;

/**
 * Registra en MySQL los pagos que Stripe notifica por el webhook (RF 2.1).
 *
 * No envía correos: cuando un pago queda confirmado dispara el evento PagoConfirmado
 * y el listener EnviarConfirmacionDePago se encarga del aviso con SendGrid.
 */
class PagoService
{
    use EscribeLogs;

    private const SERVICIO = 'webhook';

    public function __construct(private readonly StripeService $stripe) {}

    /**
     * Procesa un evento de Stripe ya verificado.
     *
     * @return Pago|null el pago registrado o actualizado; null si el evento no se procesa
     */
    public function procesarEvento(Event $evento): ?Pago
    {
        $intento = $evento->data->object ?? null;

        if (! $intento instanceof PaymentIntent || ! in_array($evento->type, ['payment_intent.succeeded', 'payment_intent.payment_failed'], true)) {
            $this->escribirLog('info', "Evento {$evento->type} recibido sin acción", ['evento' => $evento->id]);

            return null;
        }

        // La cuenta de Stripe puede estar compartida: solo se registran los pagos de este checkout
        if (! $this->stripe->esDeEstaAplicacion($intento)) {
            $this->escribirLog('info', 'Evento ignorado: el pago no se creó en este checkout', [
                'evento' => $evento->id,
                'tipo' => $evento->type,
                'payment_intent' => $intento->id,
            ]);

            return null;
        }

        return $evento->type === 'payment_intent.succeeded'
            ? $this->registrarExitoso($intento, $evento)
            : $this->registrarFallido($intento, $evento);
    }

    private function registrarExitoso(PaymentIntent $intento, Event $evento): Pago
    {
        $datos = $this->stripe->datosDelPago($intento);

        // Un registro por PaymentIntent: si Stripe reenvía el evento se actualiza, no se duplica
        $pago = Pago::updateOrCreate(
            ['payment_intent_id' => $datos['id']],
            [
                'estado' => Pago::EXITOSO,
                'monto' => $datos['monto'],
                'moneda' => $datos['moneda'],
                'cliente_nombre' => $datos['cliente_nombre'],
                'cliente_email' => $datos['cliente_email'],
                'concepto' => $datos['concepto'],
                'tarjeta' => $datos['tarjeta'],
                'error' => null,
                'stripe_event_id' => $evento->id,
                'pagado_at' => Carbon::createFromTimestamp($evento->created, config('app.timezone')),
            ],
        );

        $this->escribirLog('info', 'Pago exitoso registrado en la base de datos', [
            'evento' => $evento->id,
            'payment_intent' => $pago->payment_intent_id,
            'monto' => $pago->monto_formateado,
            'cliente' => $pago->cliente_nombre,
        ]);

        // El correo de confirmación se envía una sola vez aunque el evento llegue repetido
        if ($pago->correo_enviado_at === null) {
            PagoConfirmado::dispatch($pago);
        }

        return $pago;
    }

    private function registrarFallido(PaymentIntent $intento, Event $evento): Pago
    {
        $existente = Pago::where('payment_intent_id', $intento->id)->first();

        // Stripe no garantiza el orden de los eventos: un rechazo viejo no reemplaza un pago exitoso
        if ($existente?->fueExitoso()) {
            $this->escribirLog('info', 'Rechazo ignorado: el pago ya estaba registrado como exitoso', ['evento' => $evento->id, 'payment_intent' => $intento->id]);

            return $existente;
        }

        $datos = $this->stripe->datosDelPago($intento);

        $pago = Pago::updateOrCreate(
            ['payment_intent_id' => $datos['id']],
            [
                'estado' => Pago::FALLIDO,
                'monto' => $datos['monto'],
                'moneda' => $datos['moneda'],
                'cliente_nombre' => $datos['cliente_nombre'],
                'cliente_email' => $datos['cliente_email'],
                'concepto' => $datos['concepto'],
                'tarjeta' => $datos['tarjeta'],
                'error' => $datos['error'],
                'stripe_event_id' => $evento->id,
                'pagado_at' => null,
            ],
        );

        $this->escribirLog('warning', 'Pago rechazado: '.($pago->error ?? 'sin motivo informado'), [
            'evento' => $evento->id,
            'payment_intent' => $pago->payment_intent_id,
            'monto' => $pago->monto_formateado,
            'tarjeta' => $pago->tarjeta,
            'cliente' => $pago->cliente_nombre,
        ]);

        return $pago;
    }
}
