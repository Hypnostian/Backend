<?php

namespace Tests\Feature;

use App\Exceptions\PagoInvalidoException;
use App\Exceptions\ServicioExternoException;
use App\Exceptions\WebhookInvalidoException;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Tests\Support\InteractuaConStripe;
use Tests\TestCase;

class StripeServiceTest extends TestCase
{
    use InteractuaConStripe, RefreshDatabase;

    private function servicio(?string $llave = 'sk_test_cloudspace', ?string $secretoWebhook = 'whsec_cloudspace_pruebas'): StripeService
    {
        return new StripeService(new StripeClient(['api_key' => $llave]), $secretoWebhook);
    }

    public function test_crea_el_payment_intent_en_la_unidad_minima_de_la_moneda_y_solo_con_tarjeta(): void
    {
        $http = $this->simularStripe([[200, $this->paymentIntent(['status' => 'requires_payment_method'])]]);

        $intento = $this->servicio()->crearPaymentIntent(120000, 'COP', [
            'nombre' => self::CLIENTE,
            'email' => self::CORREO_CLIENTE,
            'concepto' => 'Sala de conferencias (2 horas)',
        ]);

        $this->assertSame('pi_3PruebaCloudSpace_secret_abc123', $intento->client_secret);
        $this->assertCount(1, $http->peticiones);

        $peticion = $http->peticiones[0];
        $this->assertSame('post', $peticion['metodo']);
        $this->assertSame('https://api.stripe.com/v1/payment_intents', $peticion['url']);
        $this->assertSame(12000000, $peticion['parametros']['amount']);
        $this->assertSame('cop', $peticion['parametros']['currency']);
        $this->assertSame(['card'], $peticion['parametros']['allowed_payment_method_types']);
        $this->assertArrayNotHasKey('payment_method_types', $peticion['parametros']);
        $this->assertSame('Sala de conferencias (2 horas) · Juan Sebastian Lizcano Jaimes', $peticion['parametros']['description']);
        $this->assertSame([
            'origen' => 'cloudspace',
            'cliente_nombre' => 'Juan Sebastian Lizcano Jaimes',
            'cliente_email' => 'jlizcano471@unab.edu.co',
            'concepto' => 'Sala de conferencias (2 horas)',
        ], $peticion['parametros']['metadata']);
    }

    public function test_sin_concepto_usa_una_descripcion_por_defecto(): void
    {
        $http = $this->simularStripe([[200, $this->paymentIntent()]]);

        $this->servicio()->crearPaymentIntent(50000, 'cop', ['nombre' => self::CLIENTE]);

        $this->assertSame('Reserva de espacio en CloudSpace Solutions · Juan Sebastian Lizcano Jaimes', $http->peticiones[0]['parametros']['description']);
        $this->assertSame(['origen' => 'cloudspace', 'cliente_nombre' => self::CLIENTE], $http->peticiones[0]['parametros']['metadata']);
    }

    public function test_reconoce_los_pagos_creados_por_este_checkout(): void
    {
        $servicio = $this->servicio();

        $this->assertTrue($servicio->esDeEstaAplicacion(PaymentIntent::constructFrom($this->paymentIntent())));
        $this->assertFalse($servicio->esDeEstaAplicacion(PaymentIntent::constructFrom($this->paymentIntentDeOtraAplicacion())));
    }

    public function test_convierte_los_decimales_a_centavos_sin_errores_de_redondeo(): void
    {
        $http = $this->simularStripe([
            [200, $this->paymentIntent()],
            [200, $this->paymentIntent()],
        ]);

        $this->servicio()->crearPaymentIntent(19.99, 'usd');
        $this->servicio()->crearPaymentIntent(45000.5, 'cop');

        $this->assertSame(1999, $http->peticiones[0]['parametros']['amount']);
        $this->assertSame(4500050, $http->peticiones[1]['parametros']['amount']);
    }

    public function test_rechaza_una_moneda_no_habilitada_sin_llamar_a_stripe(): void
    {
        $http = $this->simularStripe();

        try {
            $this->servicio()->crearPaymentIntent(100, 'btc');
            $this->fail('Se esperaba PagoInvalidoException.');
        } catch (PagoInvalidoException $e) {
            $this->assertSame('moneda', $e->campo);
            $this->assertStringContainsString('BTC no está habilitada', $e->getMessage());
        }

        $this->assertSame([], $http->peticiones);
    }

    public function test_valida_el_monto_minimo_de_cada_moneda(): void
    {
        $this->simularStripe();

        foreach ([[2499, 'cop'], [0.49, 'usd'], [0.49, 'eur'], [9.99, 'mxn']] as [$monto, $moneda]) {
            try {
                $this->servicio()->crearPaymentIntent($monto, $moneda);
                $this->fail("Se esperaba el rechazo de {$monto} {$moneda}.");
            } catch (PagoInvalidoException $e) {
                $this->assertSame('monto', $e->campo);
                $this->assertStringContainsString('monto mínimo', $e->getMessage());
            }
        }

        $this->assertSame([], $this->stripeHttp->peticiones);
    }

    public function test_valida_el_monto_maximo_y_los_decimales(): void
    {
        $this->simularStripe();

        $this->assertRechazo(fn () => $this->servicio()->crearPaymentIntent(1000000, 'cop'), 'monto máximo');
        $this->assertRechazo(fn () => $this->servicio()->crearPaymentIntent(10.555, 'usd'), 'dos decimales');

        $this->assertSame([], $this->stripeHttp->peticiones);
    }

    public function test_traduce_el_rechazo_de_stripe_por_monto_insuficiente(): void
    {
        $this->simularStripe([[400, ['error' => [
            'type' => 'invalid_request_error',
            'code' => 'amount_too_small',
            'param' => 'amount',
            'message' => 'Amount must convert to at least 50 cents.',
        ]]]]);

        $this->assertRechazo(
            fn () => $this->servicio()->crearPaymentIntent(2600, 'cop'),
            'inferior al mínimo que Stripe permite',
        );
    }

    public function test_una_llave_secreta_rechazada_es_una_falla_del_servicio(): void
    {
        $this->simularStripe([[401, ['error' => ['type' => 'invalid_request_error', 'message' => 'Invalid API Key provided: sk_test_***']]]]);

        $this->expectException(ServicioExternoException::class);
        $this->expectExceptionMessage('STRIPE_SECRET');

        $this->servicio()->crearPaymentIntent(50000, 'cop');
    }

    public function test_sin_llave_secreta_no_intenta_llamar_a_stripe(): void
    {
        $http = $this->simularStripe();

        try {
            $this->servicio(llave: null)->crearPaymentIntent(50000, 'cop');
            $this->fail('Se esperaba ServicioExternoException.');
        } catch (ServicioExternoException $e) {
            $this->assertSame('Falta configurar STRIPE_SECRET en el archivo .env.', $e->getMessage());
        }

        $this->assertSame([], $http->peticiones);
    }

    public function test_consulta_un_pago_con_el_metodo_de_pago_expandido(): void
    {
        $http = $this->simularStripe([[200, $this->paymentIntent([
            'payment_method' => ['id' => 'pm_123', 'object' => 'payment_method', 'type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4242']],
        ])]]);

        $resumen = $this->servicio()->consultarPago('pi_3PruebaCloudSpace');

        $this->assertSame('https://api.stripe.com/v1/payment_intents/pi_3PruebaCloudSpace', $http->peticiones[0]['url']);
        $this->assertSame(['payment_method'], $http->peticiones[0]['parametros']['expand']);
        $this->assertSame([
            'id' => 'pi_3PruebaCloudSpace',
            'estado' => 'succeeded',
            'estado_texto' => 'Pagado',
            'monto' => 120000.0,
            'moneda' => 'cop',
            'cliente_nombre' => 'Juan Sebastian Lizcano Jaimes',
            'cliente_email' => 'jlizcano471@unab.edu.co',
            'concepto' => 'Sala de conferencias (2 horas)',
            'tarjeta' => 'Visa •••• 4242',
            'error' => null,
        ], $resumen);
    }

    public function test_una_referencia_inexistente_es_un_dato_invalido(): void
    {
        $this->simularStripe([[404, ['error' => [
            'type' => 'invalid_request_error',
            'code' => 'resource_missing',
            'message' => "No such payment_intent: 'pi_noexiste'",
        ]]]]);

        $this->assertRechazo(fn () => $this->servicio()->consultarPago('pi_noexiste'), 'No existe un pago');
    }

    public function test_consulta_la_tarjeta_cuando_el_pago_solo_trae_el_id_del_metodo(): void
    {
        $http = $this->simularStripe([[200, [
            'id' => 'pm_456', 'object' => 'payment_method', 'type' => 'card',
            'card' => ['brand' => 'mastercard', 'last4' => '4444'],
        ]]]);

        $datos = $this->servicio()->datosDelPago(PaymentIntent::constructFrom($this->paymentIntent(['payment_method' => 'pm_456'])));

        $this->assertSame('Mastercard •••• 4444', $datos['tarjeta']);
        $this->assertSame('https://api.stripe.com/v1/payment_methods/pm_456', $http->peticiones[0]['url']);
    }

    public function test_si_no_puede_consultar_la_tarjeta_igual_entrega_los_datos_del_pago(): void
    {
        $this->simularStripe([[500, ['error' => ['type' => 'api_error', 'message' => 'Stripe no responde']]]]);

        $datos = $this->servicio()->datosDelPago(PaymentIntent::constructFrom($this->paymentIntent(['payment_method' => 'pm_456'])));

        $this->assertNull($datos['tarjeta']);
        $this->assertSame(120000.0, $datos['monto']);
    }

    public function test_construye_el_evento_cuando_la_firma_es_valida(): void
    {
        $cuerpo = $this->eventoStripe('payment_intent.succeeded');

        $evento = $this->servicio()->construirEvento($cuerpo, $this->firmaStripe($cuerpo));

        $this->assertSame('payment_intent.succeeded', $evento->type);
        $this->assertInstanceOf(PaymentIntent::class, $evento->data->object);
        $this->assertSame('pi_3PruebaCloudSpace', $evento->data->object->id);
    }

    public function test_rechaza_firmas_invalidas_vencidas_o_ausentes(): void
    {
        $cuerpo = $this->eventoStripe('payment_intent.succeeded');
        $servicio = $this->servicio();

        $firmas = [
            'otro secreto' => $this->firmaStripe($cuerpo, 'whsec_de_otra_cuenta'),
            'vencida' => $this->firmaStripe($cuerpo, momento: time() - 600),
            'mal formada' => 'firma-cualquiera',
            'ausente' => null,
        ];

        foreach ($firmas as $caso => $firma) {
            try {
                $servicio->construirEvento($cuerpo, $firma);
                $this->fail("La firma {$caso} debió rechazarse.");
            } catch (WebhookInvalidoException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_rechaza_un_cuerpo_que_no_es_json(): void
    {
        $this->expectException(WebhookInvalidoException::class);
        $this->expectExceptionMessage('no es un evento JSON válido');

        $this->servicio()->construirEvento('esto no es json', $this->firmaStripe('esto no es json'));
    }

    public function test_sin_secreto_del_webhook_no_puede_verificar_eventos(): void
    {
        $this->expectException(ServicioExternoException::class);
        $this->expectExceptionMessage('STRIPE_WEBHOOK_SECRET');

        $cuerpo = $this->eventoStripe('payment_intent.succeeded');
        $this->servicio(secretoWebhook: null)->construirEvento($cuerpo, $this->firmaStripe($cuerpo));
    }

    private function assertRechazo(callable $accion, string $mensaje): void
    {
        try {
            $accion();
            $this->fail('Se esperaba PagoInvalidoException.');
        } catch (PagoInvalidoException $e) {
            $this->assertStringContainsString($mensaje, $e->getMessage());
        }
    }
}
