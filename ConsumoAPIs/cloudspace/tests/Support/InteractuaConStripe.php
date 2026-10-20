<?php

namespace Tests\Support;

use Stripe\ApiRequestor;

/**
 * Utilidades para simular a Stripe en las pruebas: respuestas de la API y eventos firmados del webhook.
 */
trait InteractuaConStripe
{
    /** Las pruebas se hacen a nombre del autor del taller. */
    protected const CLIENTE = 'Juan Sebastian Lizcano Jaimes';

    protected const CORREO_CLIENTE = 'jlizcano471@unab.edu.co';

    protected ?StripeHttpFalso $stripeHttp = null;

    /**
     * Reemplaza el cliente HTTP del SDK por uno falso con las respuestas indicadas.
     *
     * @param  array<int, array{0: int, 1: array<string, mixed>}>  $respuestas
     */
    protected function simularStripe(array $respuestas = []): StripeHttpFalso
    {
        $this->stripeHttp = new StripeHttpFalso($respuestas);
        ApiRequestor::setHttpClient($this->stripeHttp);

        $this->beforeApplicationDestroyed(fn () => ApiRequestor::setHttpClient(null));

        return $this->stripeHttp;
    }

    /**
     * PaymentIntent tal como lo devuelve la API de Stripe.
     *
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    protected function paymentIntent(array $cambios = []): array
    {
        return array_replace_recursive([
            'id' => 'pi_3PruebaCloudSpace',
            'object' => 'payment_intent',
            'amount' => 12000000,
            'amount_received' => 12000000,
            'currency' => 'cop',
            'status' => 'succeeded',
            'client_secret' => 'pi_3PruebaCloudSpace_secret_abc123',
            'description' => 'Sala de conferencias (2 horas) · '.self::CLIENTE,
            'created' => 1760990400,
            'livemode' => false,
            'payment_method' => null,
            'latest_charge' => null,
            'last_payment_error' => null,
            'receipt_email' => null,
            'metadata' => [
                'origen' => 'cloudspace',
                'cliente_nombre' => self::CLIENTE,
                'cliente_email' => self::CORREO_CLIENTE,
                'concepto' => 'Sala de conferencias (2 horas)',
            ],
        ], $cambios);
    }

    /**
     * PaymentIntent creado por otra aplicación que usa la misma cuenta de Stripe (sin metadata de origen).
     *
     * @return array<string, mixed>
     */
    protected function paymentIntentDeOtraAplicacion(): array
    {
        $intento = $this->paymentIntent(['id' => 'pi_3OtraAplicacion', 'description' => 'Pago de prueba']);
        $intento['metadata'] = ['description' => 'Pago de prueba'];

        return $intento;
    }

    /**
     * Cuerpo JSON de un evento de Stripe con un PaymentIntent.
     *
     * @param  array<string, mixed>  $cambios
     */
    protected function eventoStripe(string $tipo, array $cambios = [], string $id = 'evt_PruebaCloudSpace'): string
    {
        return $this->eventoConObjeto($tipo, $this->paymentIntent($cambios), $id);
    }

    /**
     * @param  array<string, mixed>  $objeto
     */
    protected function eventoConObjeto(string $tipo, array $objeto, string $id = 'evt_PruebaCloudSpace'): string
    {
        return json_encode([
            'id' => $id,
            'object' => 'event',
            'api_version' => '2026-09-30.endive',
            'created' => 1760990460,
            'livemode' => false,
            'type' => $tipo,
            'data' => ['object' => $objeto],
        ]);
    }

    /**
     * Cabecera Stripe-Signature calculada igual que Stripe: HMAC-SHA256 de "timestamp.cuerpo".
     */
    protected function firmaStripe(string $cuerpo, ?string $secreto = null, ?int $momento = null): string
    {
        $momento ??= time();
        $secreto ??= config('services.stripe.webhook_secret');

        return 't='.$momento.',v1='.hash_hmac('sha256', $momento.'.'.$cuerpo, $secreto);
    }

    /**
     * Envía un evento al webhook como lo haría Stripe (cuerpo crudo + firma).
     */
    protected function enviarAlWebhook(string $cuerpo, ?string $firma = null)
    {
        $servidor = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

        if ($firma !== '') {
            $servidor['HTTP_STRIPE_SIGNATURE'] = $firma ?? $this->firmaStripe($cuerpo);
        }

        return $this->call('POST', '/stripe/webhook', [], [], [], $servidor, $cuerpo);
    }
}
