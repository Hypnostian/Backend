<?php

namespace App\Listeners;

use App\Events\PagoConfirmado;
use App\Exceptions\ServicioExternoException;
use App\Logging\EscribeLogs;
use App\Services\SendGridService;

/**
 * Envía al cliente el correo de confirmación del pago a través de SendGridService.
 * El módulo de pagos no conoce a SendGrid: solo dispara el evento PagoConfirmado.
 */
class EnviarConfirmacionDePago
{
    use EscribeLogs;

    private const SERVICIO = 'sendgrid';

    /**
     * Create the event listener.
     */
    public function __construct(private readonly SendGridService $sendGrid)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PagoConfirmado $event): void
    {
        $pago = $event->pago;

        if (blank($pago->cliente_email)) {
            $this->escribirLog('warning', 'El pago no tiene correo del cliente: no se envía confirmación', ['payment_intent' => $pago->payment_intent_id]);

            return;
        }

        try {
            $this->sendGrid->enviar(
                $pago->cliente_email,
                'Pago confirmado · '.config('app.name'),
                view('emails.pago-confirmado', ['pago' => $pago])->render(),
                view('emails.pago-confirmado-texto', ['pago' => $pago])->render(),
            );
        } catch (ServicioExternoException) {
            // El pago ya quedó registrado: el webhook igual responde 200 y el fallo queda en los logs
            $this->escribirLog('warning', 'El pago quedó registrado, pero no se pudo enviar el correo de confirmación', [
                'payment_intent' => $pago->payment_intent_id,
                'cliente' => $pago->cliente_email,
            ]);

            return;
        }

        $pago->update(['correo_enviado_at' => now()]);
    }
}
