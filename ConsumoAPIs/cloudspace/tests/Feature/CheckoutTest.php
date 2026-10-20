<?php

namespace Tests\Feature;

use App\Models\Pago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractuaConStripe;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use InteractuaConStripe, RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'monto' => '120000',
            'moneda' => 'cop',
            'nombre' => self::CLIENTE,
            'email' => self::CORREO_CLIENTE,
            'concepto' => 'Sala de conferencias (2 horas)',
        ], $cambios);
    }

    public function test_muestra_el_formulario_de_checkout_con_monto_y_moneda(): void
    {
        $this->get('/checkout')
            ->assertOk()
            ->assertViewIs('checkout.create')
            ->assertSee('name="monto"', false)
            ->assertSee('name="moneda"', false)
            ->assertSee('<option value="cop"', false)
            ->assertSee('<option value="usd"', false)
            ->assertSee('<option value="eur"', false)
            ->assertSee('<option value="mxn"', false)
            ->assertSee(route('checkout.payment-intent'));
    }

    public function test_carga_stripe_elements_con_la_llave_publicable(): void
    {
        $this->get('/checkout')
            ->assertOk()
            ->assertSee('<script src="https://js.stripe.com/v3/"></script>', false)
            ->assertSee('"pk_test_cloudspace"', false)
            ->assertSee('elements = stripe.elements({', false)
            ->assertSee('clientSecret,', false)
            ->assertSee("elements.create('payment'", false)
            ->assertSee('stripe.confirmPayment({', false)
            ->assertDontSee('sk_test_cloudspace');
    }

    public function test_precarga_el_espacio_elegido_en_el_inicio(): void
    {
        $this->get('/checkout?concepto=Oficina+privada+(1+d%C3%ADa)&monto=180000&moneda=cop')
            ->assertOk()
            ->assertSee('value="180000"', false)
            ->assertSee('value="Oficina privada (1 día)"', false)
            ->assertSee('<option value="cop" selected>', false);
    }

    public function test_ignora_una_moneda_no_habilitada_en_la_url(): void
    {
        $this->get('/checkout?moneda=btc&monto=abc')
            ->assertOk()
            ->assertSee('<option value="cop" selected>', false)
            ->assertSee('value=""', false);
    }

    public function test_avisa_cuando_falta_la_llave_publicable(): void
    {
        config(['services.stripe.key' => null]);

        $this->get('/checkout')
            ->assertOk()
            ->assertSee('Los pagos no están habilitados');
    }

    public function test_crea_el_payment_intent_y_devuelve_el_client_secret_en_json(): void
    {
        $http = $this->simularStripe([[200, $this->paymentIntent(['status' => 'requires_payment_method'])]]);

        $this->postJson('/create-payment-intent', $this->datosValidos())
            ->assertOk()
            ->assertExactJson([
                'clientSecret' => 'pi_3PruebaCloudSpace_secret_abc123',
                'paymentIntentId' => 'pi_3PruebaCloudSpace',
                'monto' => '$ 120.000 COP',
            ]);

        $this->assertSame(12000000, $http->peticiones[0]['parametros']['amount']);
        $this->assertSame('jlizcano471@unab.edu.co', $http->peticiones[0]['parametros']['metadata']['cliente_email']);
        $this->assertSame('cloudspace', $http->peticiones[0]['parametros']['metadata']['origen']);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseHas('registros', ['nivel' => 'info', 'servicio' => 'stripe', 'mensaje' => 'PaymentIntent creado']);
    }

    public function test_acepta_la_moneda_en_mayusculas(): void
    {
        $http = $this->simularStripe([[200, $this->paymentIntent()]]);

        $this->postJson('/create-payment-intent', $this->datosValidos(['monto' => '15.50', 'moneda' => 'USD']))
            ->assertOk()
            ->assertJsonPath('monto', 'US$ 15,50 USD');

        $this->assertSame('usd', $http->peticiones[0]['parametros']['currency']);
        $this->assertSame(1550, $http->peticiones[0]['parametros']['amount']);
    }

    public function test_los_datos_invalidos_responden_400_en_json_sin_llamar_a_stripe(): void
    {
        $http = $this->simularStripe();

        $this->postJson('/create-payment-intent', [
            'monto' => '-5',
            'moneda' => 'btc',
            'nombre' => '',
            'email' => 'no-es-un-correo',
            'concepto' => str_repeat('a', 151),
        ])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Revisa los datos del pago.')
            ->assertJsonValidationErrors(['monto', 'moneda', 'nombre', 'email', 'concepto']);

        $this->assertSame([], $http->peticiones);
    }

    public function test_los_mensajes_de_validacion_estan_en_espanol(): void
    {
        $this->simularStripe();

        $this->postJson('/create-payment-intent', $this->datosValidos(['monto' => '10.555', 'email' => '']))
            ->assertStatus(400)
            ->assertJsonPath('errors.email.0', 'El campo correo electrónico es obligatorio.')
            ->assertJsonPath('errors.monto.0', 'El campo monto admite 0-2 cifras decimales.');
    }

    public function test_un_monto_menor_al_minimo_de_la_moneda_responde_400(): void
    {
        $http = $this->simularStripe();

        $this->postJson('/create-payment-intent', $this->datosValidos(['monto' => '1000']))
            ->assertStatus(400)
            ->assertJsonPath('errors.monto.0', 'El monto mínimo para pagar en COP es $ 2.500 COP.');

        $this->assertSame([], $http->peticiones);
    }

    public function test_un_rechazo_de_stripe_responde_400(): void
    {
        $this->simularStripe([[400, ['error' => [
            'type' => 'invalid_request_error',
            'code' => 'amount_too_small',
            'param' => 'amount',
            'message' => 'Amount must convert to at least 50 cents.',
        ]]]]);

        $this->postJson('/create-payment-intent', $this->datosValidos(['monto' => '2600']))
            ->assertStatus(400)
            ->assertJsonPath('errors.monto.0', 'El monto es inferior al mínimo que Stripe permite para esta moneda (equivalente a 0,50 USD).');
    }

    public function test_una_falla_de_stripe_responde_500(): void
    {
        $this->simularStripe([[500, ['error' => ['type' => 'api_error', 'message' => 'Something went wrong on our end.']]]]);

        $this->postJson('/create-payment-intent', $this->datosValidos())
            ->assertStatus(500)
            ->assertJsonPath('message', 'No fue posible crear el intento de pago en Stripe: Something went wrong on our end.');

        $this->assertDatabaseHas('registros', ['nivel' => 'error', 'servicio' => 'stripe', 'mensaje' => 'Error de Stripe al crear el PaymentIntent']);
    }

    public function test_sin_llave_secreta_responde_500(): void
    {
        config(['services.stripe.secret' => null]);

        $this->postJson('/create-payment-intent', $this->datosValidos())
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Falta configurar STRIPE_SECRET en el archivo .env.']);
    }

    public function test_el_resultado_muestra_el_pago_aprobado_y_registrado(): void
    {
        $this->simularStripe([[200, $this->paymentIntent([
            'payment_method' => ['id' => 'pm_123', 'object' => 'payment_method', 'type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4242']],
        ])]]);
        Pago::factory()->create(['payment_intent_id' => 'pi_3PruebaCloudSpace', 'cliente_nombre' => self::CLIENTE, 'cliente_email' => self::CORREO_CLIENTE]);

        $this->get('/checkout/resultado?payment_intent=pi_3PruebaCloudSpace')
            ->assertOk()
            ->assertSee('¡Pago realizado, Juan!')
            ->assertSee('$ 120.000 COP')
            ->assertSee('Visa •••• 4242')
            ->assertSee('Registrado')
            ->assertSee('jlizcano471@unab.edu.co')
            ->assertDontSee('Esperando a Stripe');
    }

    public function test_el_resultado_espera_el_webhook_si_el_pago_aun_no_se_registra(): void
    {
        $this->simularStripe([[200, $this->paymentIntent()]]);

        $this->get('/checkout/resultado?payment_intent=pi_3PruebaCloudSpace')
            ->assertOk()
            ->assertSee('Esperando a Stripe')
            ->assertSee('esta página se actualiza sola');
    }

    public function test_el_resultado_de_un_pago_rechazado_muestra_el_motivo(): void
    {
        $this->simularStripe([[200, $this->paymentIntent([
            'status' => 'requires_payment_method',
            'amount_received' => 0,
            'last_payment_error' => ['type' => 'card_error', 'code' => 'card_declined', 'message' => 'Your card has insufficient funds.'],
        ])]]);

        $this->get('/checkout/resultado?payment_intent=pi_3PruebaCloudSpace')
            ->assertOk()
            ->assertSee('El pago no se completó')
            ->assertSee('Your card has insufficient funds.')
            ->assertSee('Pendiente de pago');
    }

    public function test_el_resultado_con_una_referencia_mal_formada_responde_404(): void
    {
        $this->get('/checkout/resultado?payment_intent=<script>')->assertNotFound();
        $this->get('/checkout/resultado')->assertNotFound();
    }
}
