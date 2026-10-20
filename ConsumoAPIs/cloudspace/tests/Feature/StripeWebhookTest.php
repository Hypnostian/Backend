<?php

namespace Tests\Feature;

use App\Models\Pago;
use App\Models\Registro;
use App\Services\PagoService;
use App\Services\SendGridService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use RuntimeException;
use Tests\Support\InteractuaConStripe;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use InteractuaConStripe, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ninguna prueba debe salir a internet: Stripe y SendGrid quedan simulados
        Http::preventStrayRequests();
        $this->simularStripe();
    }

    private function simularSendGrid(int $estado = 202, array $cuerpo = []): void
    {
        Http::fake(['api.sendgrid.com/*' => Http::response($cuerpo ?: null, $estado, ['X-Message-Id' => 'msg_prueba_123'])]);
    }

    public function test_registra_el_pago_exitoso_en_mysql_y_responde_200(): void
    {
        $this->simularSendGrid();

        $this->enviarAlWebhook($this->eventoStripe('payment_intent.succeeded'))
            ->assertOk()
            ->assertJson([
                'received' => true,
                'event' => 'payment_intent.succeeded',
                'pago' => ['estado' => 'exitoso'],
            ]);

        $this->assertDatabaseHas('pagos', [
            'payment_intent_id' => 'pi_3PruebaCloudSpace',
            'estado' => Pago::EXITOSO,
            'monto' => 120000.00,
            'moneda' => 'cop',
            'cliente_nombre' => 'Juan Sebastian Lizcano Jaimes',
            'cliente_email' => 'jlizcano471@unab.edu.co',
            'concepto' => 'Sala de conferencias (2 horas)',
            'stripe_event_id' => 'evt_PruebaCloudSpace',
            'error' => null,
        ]);

        $pago = Pago::sole();
        $this->assertSame(1760990460, $pago->pagado_at->getTimestamp());

        $this->assertDatabaseHas('registros', ['nivel' => 'info', 'servicio' => 'webhook', 'mensaje' => 'Pago exitoso registrado en la base de datos']);
        $this->assertDatabaseHas('registros', ['nivel' => 'info', 'servicio' => 'sendgrid', 'mensaje' => 'Correo enviado con SendGrid']);
    }

    public function test_ignora_los_pagos_que_creo_otra_aplicacion_con_la_misma_cuenta(): void
    {
        $this->simularSendGrid();

        $this->enviarAlWebhook($this->eventoConObjeto('payment_intent.succeeded', $this->paymentIntentDeOtraAplicacion()))
            ->assertOk()
            ->assertExactJson(['received' => true, 'event' => 'payment_intent.succeeded', 'pago' => null]);

        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseHas('registros', ['nivel' => 'info', 'servicio' => 'webhook', 'mensaje' => 'Evento ignorado: el pago no se creó en este checkout']);
        Http::assertNothingSent();
    }

    public function test_envia_la_confirmacion_del_pago_con_sendgrid(): void
    {
        $this->simularSendGrid();

        $this->enviarAlWebhook($this->eventoStripe('payment_intent.succeeded'))->assertOk();

        Http::assertSent(function (Request $peticion) {
            return $peticion->url() === SendGridService::URL_ENVIO
                && $peticion->hasHeader('Authorization', 'Bearer SG.cloudspace-pruebas')
                && $peticion['personalizations'] === [['to' => [['email' => 'jlizcano471@unab.edu.co']]]]
                && $peticion['from'] === ['email' => 'pagos@cloudspace.test', 'name' => 'CloudSpace Solutions']
                && $peticion['subject'] === 'Pago confirmado · CloudSpace Solutions'
                && $peticion['content'][0]['type'] === 'text/plain'
                && str_contains($peticion['content'][0]['value'], 'Monto: $ 120.000 COP')
                && $peticion['content'][1]['type'] === 'text/html'
                && str_contains($peticion['content'][1]['value'], '¡Gracias, Juan Sebastian Lizcano Jaimes!')
                && str_contains($peticion['content'][1]['value'], 'pi_3PruebaCloudSpace');
        });

        $this->assertNotNull(Pago::sole()->correo_enviado_at);
    }

    public function test_un_evento_repetido_no_duplica_el_pago_ni_el_correo(): void
    {
        $this->simularSendGrid();
        $evento = $this->eventoStripe('payment_intent.succeeded');

        $this->enviarAlWebhook($evento)->assertOk();
        $this->enviarAlWebhook($evento)->assertOk();

        $this->assertDatabaseCount('pagos', 1);
        Http::assertSentCount(1);
    }

    public function test_si_sendgrid_falla_el_pago_queda_registrado_y_responde_200(): void
    {
        $this->simularSendGrid(403, ['errors' => [['message' => 'The from address does not match a verified Sender Identity.']]]);

        $this->enviarAlWebhook($this->eventoStripe('payment_intent.succeeded'))->assertOk();

        $pago = Pago::sole();
        $this->assertTrue($pago->fueExitoso());
        $this->assertNull($pago->correo_enviado_at);

        // El error de SendGrid y su consecuencia sobre el pago quedan en los logs
        $this->assertDatabaseHas('registros', [
            'nivel' => 'error',
            'servicio' => 'sendgrid',
            'mensaje' => 'El remitente pagos@cloudspace.test no está verificado en SendGrid (Sender Identity). (SendGrid 403: The from address does not match a verified Sender Identity.)',
        ]);
        $this->assertDatabaseHas('registros', [
            'nivel' => 'warning',
            'servicio' => 'sendgrid',
            'mensaje' => 'El pago quedó registrado, pero no se pudo enviar el correo de confirmación',
        ]);
    }

    public function test_reintenta_el_correo_si_stripe_reenvia_un_evento_cuyo_correo_fallo(): void
    {
        $evento = $this->eventoStripe('payment_intent.succeeded');

        Http::fakeSequence('api.sendgrid.com/*')
            ->push(['errors' => [['message' => 'Service unavailable']]], 503)
            ->push(null, 202, ['X-Message-Id' => 'msg_reintento']);

        $this->enviarAlWebhook($evento)->assertOk();
        $this->assertNull(Pago::sole()->correo_enviado_at);

        $this->enviarAlWebhook($evento)->assertOk();
        $this->assertNotNull(Pago::sole()->correo_enviado_at);
        Http::assertSentCount(2);
    }

    public function test_un_pago_sin_correo_del_cliente_se_registra_sin_enviar_nada(): void
    {
        $this->simularSendGrid();

        $this->enviarAlWebhook($this->eventoStripe('payment_intent.succeeded', ['metadata' => ['cliente_email' => '']]))
            ->assertOk();

        $this->assertDatabaseHas('pagos', ['payment_intent_id' => 'pi_3PruebaCloudSpace', 'estado' => Pago::EXITOSO]);
        Http::assertNothingSent();
    }

    public function test_registra_el_pago_rechazado_con_su_motivo_y_sin_correo(): void
    {
        $this->simularSendGrid();

        $this->enviarAlWebhook($this->eventoStripe('payment_intent.payment_failed', [
            'status' => 'requires_payment_method',
            'amount_received' => 0,
            'last_payment_error' => [
                'type' => 'card_error',
                'code' => 'card_declined',
                'decline_code' => 'insufficient_funds',
                'message' => 'Your card has insufficient funds.',
                'payment_method' => ['id' => 'pm_999', 'object' => 'payment_method', 'type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '9995']],
            ],
        ]))
            ->assertOk()
            ->assertJsonPath('pago.estado', 'fallido');

        $this->assertDatabaseHas('pagos', [
            'payment_intent_id' => 'pi_3PruebaCloudSpace',
            'estado' => Pago::FALLIDO,
            'monto' => 120000.00,
            'tarjeta' => 'Visa •••• 9995',
            'error' => 'Your card has insufficient funds.',
            'pagado_at' => null,
        ]);
        $this->assertDatabaseHas('registros', ['nivel' => 'warning', 'servicio' => 'webhook', 'mensaje' => 'Pago rechazado: Your card has insufficient funds.']);
        Http::assertNothingSent();
    }

    public function test_un_rechazo_que_llega_tarde_no_reemplaza_un_pago_exitoso(): void
    {
        $this->simularSendGrid();

        $this->enviarAlWebhook($this->eventoStripe('payment_intent.succeeded'))->assertOk();
        $this->enviarAlWebhook($this->eventoStripe('payment_intent.payment_failed', [
            'status' => 'requires_payment_method',
            'last_payment_error' => ['type' => 'card_error', 'message' => 'Your card was declined.'],
        ], 'evt_Anterior'))->assertOk();

        $pago = Pago::sole();
        $this->assertTrue($pago->fueExitoso());
        $this->assertNull($pago->error);
    }

    public function test_un_pago_exitoso_despues_de_un_rechazo_actualiza_el_mismo_registro(): void
    {
        $this->simularSendGrid();

        $this->enviarAlWebhook($this->eventoStripe('payment_intent.payment_failed', [
            'status' => 'requires_payment_method',
            'amount_received' => 0,
            'last_payment_error' => ['type' => 'card_error', 'message' => 'Your card was declined.'],
        ], 'evt_Rechazo'))->assertOk();

        $this->enviarAlWebhook($this->eventoStripe('payment_intent.succeeded', [], 'evt_Exito'))->assertOk();

        $pago = Pago::sole();
        $this->assertTrue($pago->fueExitoso());
        $this->assertNull($pago->error);
        $this->assertSame('evt_Exito', $pago->stripe_event_id);
        Http::assertSentCount(1);
    }

    public function test_consulta_la_tarjeta_si_el_evento_solo_trae_el_id_del_metodo_de_pago(): void
    {
        $this->simularSendGrid();
        $stripe = $this->simularStripe([[200, [
            'id' => 'pm_123', 'object' => 'payment_method', 'type' => 'card',
            'card' => ['brand' => 'mastercard', 'last4' => '4444'],
        ]]]);

        $this->enviarAlWebhook($this->eventoStripe('payment_intent.succeeded', ['payment_method' => 'pm_123']))->assertOk();

        $this->assertSame('Mastercard •••• 4444', Pago::sole()->tarjeta);
        $this->assertSame('https://api.stripe.com/v1/payment_methods/pm_123', $stripe->peticiones[0]['url']);
    }

    public function test_los_eventos_que_no_se_manejan_responden_200_sin_registrar_nada(): void
    {
        $this->enviarAlWebhook($this->eventoStripe('payment_intent.created', ['status' => 'requires_payment_method']))
            ->assertOk()
            ->assertExactJson(['received' => true, 'event' => 'payment_intent.created', 'pago' => null]);

        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_una_firma_invalida_responde_400_y_no_registra_nada(): void
    {
        $cuerpo = $this->eventoStripe('payment_intent.succeeded');

        $this->enviarAlWebhook($cuerpo, $this->firmaStripe($cuerpo, 'whsec_de_otra_cuenta'))
            ->assertStatus(400)
            ->assertExactJson(['message' => 'La firma del webhook no es válida.']);

        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseHas('registros', ['nivel' => 'warning', 'servicio' => 'webhook', 'mensaje' => 'Webhook rechazado: La firma del webhook no es válida.']);
        Http::assertNothingSent();
    }

    public function test_un_evento_alterado_despues_de_firmado_responde_400(): void
    {
        $cuerpo = $this->eventoStripe('payment_intent.succeeded');
        $firma = $this->firmaStripe($cuerpo);

        $alterado = str_replace('12000000', '100', $cuerpo);

        $this->enviarAlWebhook($alterado, $firma)->assertStatus(400);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_sin_cabecera_stripe_signature_responde_400(): void
    {
        $this->enviarAlWebhook($this->eventoStripe('payment_intent.succeeded'), '')
            ->assertStatus(400)
            ->assertExactJson(['message' => 'Falta la cabecera Stripe-Signature.']);
    }

    public function test_una_firma_vencida_responde_400(): void
    {
        $cuerpo = $this->eventoStripe('payment_intent.succeeded');

        $this->enviarAlWebhook($cuerpo, $this->firmaStripe($cuerpo, momento: time() - 3600))
            ->assertStatus(400);
    }

    public function test_un_cuerpo_que_no_es_json_responde_400(): void
    {
        $this->enviarAlWebhook('no-es-json')
            ->assertStatus(400)
            ->assertExactJson(['message' => 'El contenido del webhook no es un evento JSON válido.']);
    }

    public function test_sin_secreto_del_webhook_configurado_responde_500(): void
    {
        config(['services.stripe.webhook_secret' => null]);
        $cuerpo = $this->eventoStripe('payment_intent.succeeded');

        $this->enviarAlWebhook($cuerpo, $this->firmaStripe($cuerpo, 'whsec_cloudspace_pruebas'))
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Falta configurar STRIPE_WEBHOOK_SECRET en el archivo .env.']);

        $this->assertDatabaseHas('registros', ['nivel' => 'error', 'servicio' => 'webhook']);
    }

    public function test_si_no_se_puede_guardar_el_pago_responde_500_para_que_stripe_reintente(): void
    {
        $this->mock(PagoService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('procesarEvento')
            ->andThrow(new RuntimeException('SQLSTATE[HY000] [2002] Connection refused')));

        $this->enviarAlWebhook($this->eventoStripe('payment_intent.succeeded'))
            ->assertStatus(500)
            ->assertExactJson(['message' => 'No se pudo registrar el pago. Stripe reintentará el envío.']);

        $registro = Registro::where('nivel', 'error')->sole();
        $this->assertSame('webhook', $registro->servicio);
        $this->assertSame('No se pudo registrar el pago del evento', $registro->mensaje);
        $this->assertSame('SQLSTATE[HY000] [2002] Connection refused', $registro->contexto['detalle']);
    }
}
