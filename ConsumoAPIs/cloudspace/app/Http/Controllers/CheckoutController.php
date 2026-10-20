<?php

namespace App\Http\Controllers;

use App\Exceptions\PagoInvalidoException;
use App\Exceptions\ServicioExternoException;
use App\Http\Requests\CrearPaymentIntentRequest;
use App\Models\Pago;
use App\Services\StripeService;
use App\Support\Dinero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * RF 1.1: formulario de checkout con el monto y la moneda. Las tarjetas de espacios
     * del inicio llegan con el concepto, el monto y la moneda ya diligenciados.
     */
    public function create(Request $request): View
    {
        $moneda = strtolower((string) $request->query('moneda', 'cop'));

        return view('checkout.create', [
            'stripeKey' => config('services.stripe.key'),
            'monedas' => StripeService::MONEDAS,
            'valores' => [
                'concepto' => Str::limit((string) $request->query('concepto', ''), 150, ''),
                'monto' => is_numeric($request->query('monto')) ? $request->query('monto') : '',
                'moneda' => array_key_exists($moneda, StripeService::MONEDAS) ? $moneda : 'cop',
            ],
        ]);
    }

    /**
     * RF 1.2: delega la creación del cobro a StripeService y devuelve el clientSecret en JSON.
     */
    public function store(CrearPaymentIntentRequest $request, StripeService $stripe): JsonResponse
    {
        $datos = $request->validated();

        try {
            $intento = $stripe->crearPaymentIntent((float) $datos['monto'], $datos['moneda'], [
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'concepto' => $datos['concepto'] ?? null,
            ]);
        } catch (PagoInvalidoException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => [$e->campo => [$e->getMessage()]],
            ], 400);
        } catch (ServicioExternoException $e) {
            // StripeService ya dejó el detalle del error en los logs
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json([
            'clientSecret' => $intento->client_secret,
            'paymentIntentId' => $intento->id,
            'monto' => Dinero::formatear($datos['monto'], $datos['moneda']),
        ]);
    }

    /**
     * Resultado del pago: estado en Stripe y si el webhook ya lo registró en la base de datos.
     */
    public function resultado(Request $request, StripeService $stripe): View
    {
        $id = (string) $request->query('payment_intent', '');

        abort_unless(preg_match('/^pi_[A-Za-z0-9]+$/', $id) === 1, 404);

        try {
            $resumen = $stripe->consultarPago($id);
            $error = null;
        } catch (PagoInvalidoException|ServicioExternoException $e) {
            $resumen = null;
            $error = $e->getMessage();
        }

        return view('checkout.resultado', [
            'id' => $id,
            'resumen' => $resumen,
            'error' => $error,
            'pago' => Pago::where('payment_intent_id', $id)->first(),
        ]);
    }
}
