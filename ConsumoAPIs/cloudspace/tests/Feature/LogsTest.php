<?php

namespace Tests\Feature;

use App\Models\Registro;
use App\Services\SendGridService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogsTest extends TestCase
{
    use RefreshDatabase;

    private function log(string $nivel, string $servicio, string $mensaje, array $contexto = []): void
    {
        Log::channel('integraciones')->log($nivel, $mensaje, ['servicio' => $servicio, ...$contexto]);
    }

    public function test_muestra_un_mensaje_cuando_no_hay_registros(): void
    {
        $this->get('/logs')
            ->assertOk()
            ->assertViewIs('logs.index')
            ->assertSee('No hay registros todavía');
    }

    public function test_el_canal_integraciones_guarda_cada_log_en_la_base_de_datos(): void
    {
        $this->log('error', 'stripe', 'Stripe rechazó la llave secreta (STRIPE_SECRET)', ['detalle' => 'Invalid API Key provided']);

        $registro = Registro::sole();
        $this->assertSame('error', $registro->nivel);
        $this->assertSame('stripe', $registro->servicio);
        $this->assertSame('Stripe rechazó la llave secreta (STRIPE_SECRET)', $registro->mensaje);
        $this->assertSame(['detalle' => 'Invalid API Key provided'], $registro->contexto);
        $this->assertNotNull($registro->created_at);
    }

    public function test_los_detalles_se_muestran_en_orden_fijo_y_con_nombres_legibles(): void
    {
        $this->log('error', 'sendgrid', 'SendGrid rechazó la API key.', [
            'detalle' => 'The provided authorization grant is invalid, expired, or revoked',
            'destinatarios' => 'jlizcano471@unab.edu.co',
            'estado_http' => 401,
        ]);

        $this->assertSame([
            'Destinatarios' => 'jlizcano471@unab.edu.co',
            'HTTP' => '401',
            'Detalle' => 'The provided authorization grant is invalid, expired, or revoked',
        ], Registro::sole()->detalles());
    }

    public function test_un_envio_fallido_de_sendgrid_aparece_en_los_logs_con_su_detalle(): void
    {
        Http::preventStrayRequests();
        Http::fake([SendGridService::URL_ENVIO => Http::response(['errors' => [['message' => 'The provided authorization grant is invalid, expired, or revoked']]], 401)]);

        $this->from('/email')->post('/email', [
            'destinatarios' => 'jlizcano471@unab.edu.co',
            'asunto' => 'Prueba de envío · CloudSpace Solutions',
            'mensaje' => 'Correo de prueba de Juan Sebastian Lizcano Jaimes',
        ])->assertSessionHas('error');

        $this->get('/logs')
            ->assertOk()
            ->assertSeeInOrder(['Errores', '1'])
            ->assertSee('Error')
            ->assertSee('SendGrid')
            ->assertSee('SendGrid rechazó la API key. Revisa SENDGRID_API_KEY en el archivo .env.')
            ->assertSee('The provided authorization grant is invalid, expired, or revoked')
            ->assertSee('jlizcano471@unab.edu.co')
            ->assertSee('401');
    }

    public function test_el_filtro_de_problemas_muestra_solo_errores_y_advertencias(): void
    {
        $this->log('info', 'stripe', 'PaymentIntent creado');
        $this->log('warning', 'webhook', 'Webhook rechazado: La firma del webhook no es válida.');
        $this->log('error', 'sendgrid', 'SendGrid rechazó la API key.');

        $this->get('/logs?filtro=problemas')
            ->assertOk()
            ->assertSee('Webhook rechazado: La firma del webhook no es válida.')
            ->assertSee('SendGrid rechazó la API key.')
            ->assertDontSee('PaymentIntent creado');
    }

    public function test_filtra_por_servicio(): void
    {
        $this->log('info', 'stripe', 'PaymentIntent creado');
        $this->log('info', 'webhook', 'Pago exitoso registrado en la base de datos');
        $this->log('error', 'sendgrid', 'SendGrid rechazó la API key.');

        $this->get('/logs?filtro=webhook')
            ->assertOk()
            ->assertSee('Pago exitoso registrado en la base de datos')
            ->assertDontSee('PaymentIntent creado')
            ->assertDontSee('SendGrid rechazó la API key.');
    }

    public function test_muestra_el_conteo_y_los_registros_mas_recientes_primero(): void
    {
        $this->log('info', 'stripe', 'Primer evento');
        $this->log('warning', 'webhook', 'Segundo evento');
        $this->log('error', 'sendgrid', 'Tercer evento');

        $this->get('/logs')
            ->assertOk()
            ->assertSeeInOrder(['Errores', '1', 'Advertencias', '1', 'Registros en total', '3'])
            ->assertSeeInOrder(['Tercer evento', 'Segundo evento', 'Primer evento']);
    }

    public function test_pagina_de_veinte_en_veinte(): void
    {
        foreach (range(1, 25) as $numero) {
            $this->log('info', 'stripe', "Evento {$numero}");
        }

        $this->get('/logs')->assertOk()->assertSee('Mostrando 1 a 20 de 25 registros');
        $this->get('/logs?page=2')->assertOk()->assertSee('Mostrando 21 a 25 de 25 registros');
    }
}
