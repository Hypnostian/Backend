<?php

namespace App\Services;

use App\Exceptions\PagoInvalidoException;
use App\Exceptions\ServicioExternoException;
use App\Exceptions\WebhookInvalidoException;
use App\Logging\EscribeLogs;
use App\Support\Dinero;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Capa de servicio para la API de Stripe con su SDK oficial (stripe/stripe-php).
 *
 * Es la única clase de la aplicación que habla con Stripe: valida y crea los intentos
 * de pago (PaymentIntent), consulta su estado y verifica la firma de los eventos que
 * llegan al webhook. Los controladores solo reciben datos simples o excepciones propias.
 */
class StripeService
{
    use EscribeLogs;

    private const SERVICIO = 'stripe';

    /**
     * Marca que se guarda en la metadata de cada PaymentIntent creado por esta aplicación.
     * Si la cuenta de Stripe se comparte, el webhook ignora los pagos de otras aplicaciones.
     */
    public const ORIGEN = 'cloudspace';

    /**
     * Monedas habilitadas en el checkout y el monto mínimo que Stripe acepta en cada una
     * (equivalente aproximado a 0,50 USD). Todas usan dos decimales en Stripe.
     */
    public const MONEDAS = [
        'cop' => ['nombre' => 'Peso colombiano', 'minimo' => 2500],
        'usd' => ['nombre' => 'Dólar estadounidense', 'minimo' => 0.50],
        'eur' => ['nombre' => 'Euro', 'minimo' => 0.50],
        'mxn' => ['nombre' => 'Peso mexicano', 'minimo' => 10],
    ];

    /**
     * Stripe admite montos de hasta ocho dígitos en la unidad mínima (99.999.999 centavos).
     */
    public const MONTO_MAXIMO = 999999.99;

    private const ESTADOS = [
        'succeeded' => 'Pagado',
        'processing' => 'Procesando',
        'requires_payment_method' => 'Pendiente de pago',
        'requires_confirmation' => 'Pendiente de confirmación',
        'requires_action' => 'Requiere autenticación',
        'requires_capture' => 'Autorizado',
        'canceled' => 'Cancelado',
    ];

    private const MARCAS = [
        'amex' => 'American Express',
        'diners' => 'Diners Club',
        'discover' => 'Discover',
        'jcb' => 'JCB',
        'mastercard' => 'Mastercard',
        'unionpay' => 'UnionPay',
        'visa' => 'Visa',
    ];

    public function __construct(
        private readonly StripeClient $stripe,
        private readonly ?string $secretoWebhook = null,
    ) {}

    /**
     * Valida el cobro y crea el PaymentIntent en Stripe. El client_secret que devuelve
     * es lo único que necesita el navegador para confirmar el pago con Stripe Elements.
     *
     * @param  array{nombre?: string|null, email?: string|null, concepto?: string|null}  $cliente
     *
     * @throws PagoInvalidoException si el monto o la moneda no son válidos
     * @throws ServicioExternoException si Stripe no está configurado o no responde
     */
    public function crearPaymentIntent(float $monto, string $moneda, array $cliente = []): PaymentIntent
    {
        $moneda = strtolower(trim($moneda));

        $this->validarCobro($monto, $moneda);
        $this->verificarLlaveSecreta();

        $nombre = $cliente['nombre'] ?? null;
        $concepto = $cliente['concepto'] ?? null;
        $total = Dinero::formatear($monto, $moneda);

        try {
            $intento = $this->stripe->paymentIntents->create([
                'amount' => $this->aUnidadMinima($monto),
                'currency' => $moneda,
                // Desde la API 2026-09-30.endive reemplaza a payment_method_types: solo se acepta tarjeta
                'allowed_payment_method_types' => ['card'],
                'description' => implode(' · ', array_filter([$concepto ?: 'Reserva de espacio en CloudSpace Solutions', $nombre])),
                // Stripe devuelve la metadata en el webhook: así se sabe a quién confirmar el pago
                'metadata' => array_filter([
                    'origen' => self::ORIGEN,
                    'cliente_nombre' => $nombre,
                    'cliente_email' => $cliente['email'] ?? null,
                    'concepto' => $concepto,
                ]),
            ]);
        } catch (InvalidRequestException $e) {
            $this->escribirLog('warning', 'Stripe rechazó el cobro', ['monto' => $total, 'codigo' => $e->getStripeCode(), 'detalle' => $e->getMessage()]);

            throw new PagoInvalidoException($this->traducirRechazo($e), $e->getStripeParam() === 'currency' ? 'moneda' : 'monto', $e);
        } catch (AuthenticationException $e) {
            $this->escribirLog('error', 'Stripe rechazó la llave secreta (STRIPE_SECRET)', ['detalle' => $e->getMessage()]);

            throw new ServicioExternoException('Stripe rechazó la llave secreta. Revisa STRIPE_SECRET en el archivo .env.', 0, $e);
        } catch (ApiErrorException $e) {
            $this->escribirLog('error', 'Error de Stripe al crear el PaymentIntent', ['monto' => $total, 'estado_http' => $e->getHttpStatus(), 'detalle' => $e->getMessage()]);

            throw new ServicioExternoException('No fue posible crear el intento de pago en Stripe: '.$e->getMessage(), 0, $e);
        }

        $this->escribirLog('info', 'PaymentIntent creado', ['payment_intent' => $intento->id, 'monto' => $total, 'cliente' => $nombre]);

        return $intento;
    }

    /**
     * Reglas de negocio del cobro: moneda habilitada y monto entre el mínimo de Stripe y el máximo.
     *
     * @throws PagoInvalidoException
     */
    public function validarCobro(float $monto, string $moneda): void
    {
        if (! array_key_exists($moneda, self::MONEDAS)) {
            throw new PagoInvalidoException('La moneda '.strtoupper($moneda).' no está habilitada para pagos.', 'moneda');
        }

        if (round($monto, 2) !== $monto) {
            throw new PagoInvalidoException('El monto admite como máximo dos decimales.');
        }

        $minimo = self::MONEDAS[$moneda]['minimo'];

        if ($monto < $minimo) {
            throw new PagoInvalidoException('El monto mínimo para pagar en '.strtoupper($moneda).' es '.Dinero::formatear($minimo, $moneda).'.');
        }

        if ($monto > self::MONTO_MAXIMO) {
            throw new PagoInvalidoException('El monto máximo por transacción es '.Dinero::formatear(self::MONTO_MAXIMO, $moneda).'.');
        }
    }

    /**
     * Consulta un PaymentIntent y devuelve sus datos listos para mostrar.
     *
     * @return array{id: string, estado: string, estado_texto: string, monto: float, moneda: string, cliente_nombre: string|null, cliente_email: string|null, concepto: string|null, tarjeta: string|null, error: string|null}
     *
     * @throws PagoInvalidoException si la referencia no existe en Stripe
     * @throws ServicioExternoException si Stripe no está configurado o no responde
     */
    public function consultarPago(string $paymentIntentId): array
    {
        $this->verificarLlaveSecreta();

        try {
            $intento = $this->stripe->paymentIntents->retrieve($paymentIntentId, ['expand' => ['payment_method']]);
        } catch (InvalidRequestException $e) {
            $this->escribirLog('warning', 'Se consultó un pago que no existe en Stripe', ['payment_intent' => $paymentIntentId, 'detalle' => $e->getMessage()]);

            throw new PagoInvalidoException('No existe un pago con la referencia '.$paymentIntentId.'.', 'payment_intent', $e);
        } catch (ApiErrorException $e) {
            $this->escribirLog('error', 'Error de Stripe al consultar el pago', ['payment_intent' => $paymentIntentId, 'estado_http' => $e->getHttpStatus(), 'detalle' => $e->getMessage()]);

            throw new ServicioExternoException('No fue posible consultar el pago en Stripe: '.$e->getMessage(), 0, $e);
        }

        return $this->datosDelPago($intento);
    }

    /**
     * Convierte un PaymentIntent (de la API o del webhook) en los datos que guarda la aplicación.
     *
     * @return array{id: string, estado: string, estado_texto: string, monto: float, moneda: string, cliente_nombre: string|null, cliente_email: string|null, concepto: string|null, tarjeta: string|null, error: string|null}
     */
    public function datosDelPago(PaymentIntent $intento): array
    {
        $metadata = $intento->metadata ?? null;
        $error = $intento->last_payment_error ?? null;

        return [
            'id' => $intento->id,
            'estado' => $intento->status,
            'estado_texto' => self::ESTADOS[$intento->status] ?? $intento->status,
            // En un pago exitoso se usa lo realmente cobrado; si falló, lo que se intentó cobrar
            'monto' => $this->aUnidadPrincipal(($intento->amount_received ?? 0) ?: $intento->amount),
            'moneda' => $intento->currency,
            'cliente_nombre' => ($metadata['cliente_nombre'] ?? null) ?: null,
            'cliente_email' => ($metadata['cliente_email'] ?? null) ?: (($intento->receipt_email ?? null) ?: null),
            'concepto' => ($metadata['concepto'] ?? null) ?: null,
            'tarjeta' => $this->describirTarjeta($intento->payment_method ?? ($error->payment_method ?? null)),
            'error' => $error->message ?? null,
        ];
    }

    /**
     * Indica si el PaymentIntent lo creó este checkout (metadata origen = cloudspace).
     */
    public function esDeEstaAplicacion(PaymentIntent $intento): bool
    {
        return ($intento->metadata['origen'] ?? null) === self::ORIGEN;
    }

    /**
     * Verifica la firma (cabecera Stripe-Signature) con el secreto del webhook y construye el evento.
     *
     * @throws WebhookInvalidoException si la petición no viene de Stripe o está dañada
     * @throws ServicioExternoException si falta STRIPE_WEBHOOK_SECRET
     */
    public function construirEvento(string $contenido, ?string $firma): Event
    {
        if (blank($this->secretoWebhook)) {
            $this->escribirLog('error', 'Llegó un evento al webhook, pero falta configurar STRIPE_WEBHOOK_SECRET', servicio: 'webhook');

            throw new ServicioExternoException('Falta configurar STRIPE_WEBHOOK_SECRET en el archivo .env.');
        }

        try {
            if (blank($firma)) {
                throw new WebhookInvalidoException('Falta la cabecera Stripe-Signature.');
            }

            try {
                return Webhook::constructEvent($contenido, $firma, $this->secretoWebhook);
            } catch (SignatureVerificationException $e) {
                throw new WebhookInvalidoException('La firma del webhook no es válida.', 0, $e);
            } catch (UnexpectedValueException $e) {
                throw new WebhookInvalidoException('El contenido del webhook no es un evento JSON válido.', 0, $e);
            }
        } catch (WebhookInvalidoException $e) {
            $this->escribirLog('warning', 'Webhook rechazado: '.$e->getMessage(), ['detalle' => $e->getPrevious()?->getMessage()], 'webhook');

            throw $e;
        }
    }

    /**
     * "Visa •••• 4242". Si el webhook solo trae el ID del método de pago, lo consulta en Stripe;
     * un fallo en esa consulta no impide registrar el pago.
     */
    private function describirTarjeta(PaymentMethod|string|null $metodo): ?string
    {
        if (is_string($metodo)) {
            try {
                $metodo = $this->stripe->paymentMethods->retrieve($metodo);
            } catch (ApiErrorException $e) {
                $this->escribirLog('warning', 'No se pudo consultar la tarjeta del pago en Stripe', ['metodo_de_pago' => $metodo, 'detalle' => $e->getMessage()]);

                return null;
            }
        }

        $tarjeta = $metodo?->card ?? null;

        if (! $tarjeta) {
            return null;
        }

        return (self::MARCAS[$tarjeta->brand] ?? ucfirst((string) $tarjeta->brand)).' •••• '.$tarjeta->last4;
    }

    /**
     * Mensaje en español para los rechazos más comunes de Stripe al crear el cobro.
     */
    private function traducirRechazo(InvalidRequestException $e): string
    {
        return match ($e->getStripeCode()) {
            'amount_too_small' => 'El monto es inferior al mínimo que Stripe permite para esta moneda (equivalente a 0,50 USD).',
            'amount_too_large' => 'El monto supera el máximo que Stripe permite por transacción.',
            default => 'Stripe rechazó los datos del pago: '.$e->getMessage(),
        };
    }

    /**
     * @throws ServicioExternoException
     */
    private function verificarLlaveSecreta(): void
    {
        if (blank($this->stripe->getApiKey())) {
            $this->escribirLog('error', 'Falta configurar STRIPE_SECRET en el archivo .env');

            throw new ServicioExternoException('Falta configurar STRIPE_SECRET en el archivo .env.');
        }
    }

    /**
     * 45000.50 COP -> 4500050 (Stripe trabaja con la unidad mínima de la moneda).
     */
    private function aUnidadMinima(float $monto): int
    {
        return (int) round($monto * 100);
    }

    private function aUnidadPrincipal(int $unidades): float
    {
        return $unidades / 100;
    }
}
