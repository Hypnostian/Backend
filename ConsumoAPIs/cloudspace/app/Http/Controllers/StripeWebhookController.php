<?php

namespace App\Http\Controllers;

use App\Exceptions\ServicioExternoException;
use App\Exceptions\WebhookInvalidoException;
use App\Logging\EscribeLogs;
use App\Services\PagoService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class StripeWebhookController extends Controller
{
    use EscribeLogs;

    private const SERVICIO = 'webhook';

    /**
     * RF 2.1: recibe las notificaciones de Stripe, registra el pago y responde 200 OK.
     * La ruta está excluida de CSRF; su autenticidad la garantiza la firma Stripe-Signature.
     */
    public function __invoke(Request $request, StripeService $stripe, PagoService $pagos): JsonResponse
    {
        try {
            $evento = $stripe->construirEvento($request->getContent(), $request->header('Stripe-Signature'));
        } catch (WebhookInvalidoException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (ServicioExternoException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        try {
            $pago = $pagos->procesarEvento($evento);
        } catch (Throwable $e) {
            report($e);
            $this->escribirLog('error', 'No se pudo registrar el pago del evento', [
                'evento' => $evento->id,
                'tipo' => $evento->type,
                'detalle' => $e->getMessage(),
            ]);

            // Con un código distinto de 2xx Stripe vuelve a enviar el evento más tarde
            return response()->json(['message' => 'No se pudo registrar el pago. Stripe reintentará el envío.'], 500);
        }

        return response()->json([
            'received' => true,
            'event' => $evento->type,
            'pago' => $pago ? ['id' => $pago->id, 'estado' => $pago->estado] : null,
        ]);
    }
}
