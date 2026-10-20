<?php

namespace Tests\Feature;

use App\Services\SendGridService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnvioCorreoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function simularSendGrid(int $estado = 202, array $cuerpo = []): void
    {
        Http::fake([SendGridService::URL_ENVIO => Http::response($cuerpo ?: null, $estado, ['X-Message-Id' => 'msg_prueba_123'])]);
    }

    public function test_muestra_el_formulario_con_datos_de_prueba(): void
    {
        $this->get('/email')
            ->assertOk()
            ->assertViewIs('email.create')
            ->assertSee('name="destinatarios"', false)
            ->assertSee('name="asunto"', false)
            ->assertSee('name="mensaje"', false)
            ->assertSee('value="Prueba de envío · CloudSpace Solutions"', false)
            ->assertSee('Este es un correo de prueba enviado desde el módulo de notificaciones')
            ->assertSee('pagos@cloudspace.test')
            ->assertDontSee('SendGrid no está configurado');
    }

    public function test_envia_el_correo_a_varios_destinatarios_con_la_api_de_sendgrid(): void
    {
        $this->simularSendGrid();

        $this->post('/email', [
            'destinatarios' => "ana@cloudspace.test, carlos@cloudspace.test\nsofia@cloudspace.test",
            'asunto' => 'Bienvenida a CloudSpace',
            'mensaje' => "Hola:\nTu cuenta está lista.",
        ])
            ->assertRedirect('/email')
            ->assertSessionHas('enviado', [
                'destinatarios' => ['ana@cloudspace.test', 'carlos@cloudspace.test', 'sofia@cloudspace.test'],
                'id' => 'msg_prueba_123',
                'sandbox' => false,
            ]);

        Http::assertSent(function (Request $peticion) {
            return $peticion->method() === 'POST'
                && $peticion->url() === 'https://api.sendgrid.com/v3/mail/send'
                && $peticion->hasHeader('Authorization', 'Bearer SG.cloudspace-pruebas')
                && $peticion['personalizations'] === [
                    ['to' => [['email' => 'ana@cloudspace.test']]],
                    ['to' => [['email' => 'carlos@cloudspace.test']]],
                    ['to' => [['email' => 'sofia@cloudspace.test']]],
                ]
                && $peticion['subject'] === 'Bienvenida a CloudSpace'
                && $peticion['content'][0] === ['type' => 'text/plain', 'value' => "Hola:\nTu cuenta está lista."]
                && $peticion['content'][1]['type'] === 'text/html'
                && str_contains($peticion['content'][1]['value'], "Hola:<br />\nTu cuenta está lista.");
        });
        Http::assertSentCount(1);
    }

    public function test_muestra_la_confirmacion_del_envio(): void
    {
        $this->simularSendGrid();

        $this->followingRedirects()
            ->post('/email', [
                'destinatarios' => 'ana@cloudspace.test',
                'asunto' => 'Prueba',
                'mensaje' => 'Mensaje de prueba',
            ])
            ->assertOk()
            ->assertSee('Correo enviado con éxito a través de la API de SendGrid.')
            ->assertSee('ana@cloudspace.test')
            ->assertSee('msg_prueba_123');
    }

    public function test_los_destinatarios_repetidos_reciben_un_solo_correo(): void
    {
        $this->simularSendGrid();

        $this->post('/email', [
            'destinatarios' => "ana@cloudspace.test; ANA@cloudspace.test\n ana@cloudspace.test ",
            'asunto' => 'Prueba',
            'mensaje' => 'Mensaje',
        ])->assertRedirect('/email');

        Http::assertSent(fn (Request $peticion) => count($peticion['personalizations']) === 1);
    }

    public function test_valida_el_formulario_sin_llamar_a_sendgrid(): void
    {
        $this->simularSendGrid();

        $this->from('/email')
            ->post('/email', ['destinatarios' => 'no-es-correo, ana@cloudspace.test', 'asunto' => '', 'mensaje' => ''])
            ->assertRedirect('/email')
            ->assertSessionHasErrors([
                'destinatarios' => 'Estos destinatarios no son correos válidos: no-es-correo.',
                'asunto' => 'El campo asunto es obligatorio.',
                'mensaje' => 'El campo mensaje es obligatorio.',
            ]);

        Http::assertNothingSent();
    }

    public function test_limita_la_cantidad_de_destinatarios(): void
    {
        $this->simularSendGrid();
        $correos = collect(range(1, 11))->map(fn ($n) => "persona{$n}@cloudspace.test")->implode(', ');

        $this->from('/email')
            ->post('/email', ['destinatarios' => $correos, 'asunto' => 'Prueba', 'mensaje' => 'Mensaje'])
            ->assertSessionHasErrors(['destinatarios' => 'Puedes enviar a máximo 10 destinatarios a la vez.']);

        Http::assertNothingSent();
    }

    public function test_muestra_el_error_de_sendgrid_y_conserva_lo_escrito(): void
    {
        $this->simularSendGrid(403, ['errors' => [[
            'message' => 'The from address does not match a verified Sender Identity. Mail cannot be sent until this error is resolved.',
            'field' => 'from',
        ]]]);

        $this->from('/email')
            ->post('/email', ['destinatarios' => 'ana@cloudspace.test', 'asunto' => 'Prueba', 'mensaje' => 'Mensaje'])
            ->assertRedirect('/email')
            ->assertSessionHas('error', fn (string $error) => str_contains($error, 'El remitente pagos@cloudspace.test no está verificado en SendGrid'))
            ->assertSessionHasInput('destinatarios', 'ana@cloudspace.test');
    }

    public function test_avisa_cuando_sendgrid_no_esta_configurado(): void
    {
        config(['services.sendgrid.key' => null]);

        $this->get('/email')->assertSee('SendGrid no está configurado');

        $this->from('/email')
            ->post('/email', ['destinatarios' => 'ana@cloudspace.test', 'asunto' => 'Prueba', 'mensaje' => 'Mensaje'])
            ->assertSessionHas('error', 'Falta configurar SENDGRID_API_KEY en el archivo .env.');

        Http::assertNothingSent();
    }

    public function test_en_modo_sandbox_sendgrid_valida_sin_entregar(): void
    {
        config(['services.sendgrid.sandbox' => true]);
        Http::fake([SendGridService::URL_ENVIO => Http::response(null, 200)]);

        $this->followingRedirects()
            ->post('/email', ['destinatarios' => 'ana@cloudspace.test', 'asunto' => 'Prueba', 'mensaje' => 'Mensaje'])
            ->assertSee('SendGrid validó el correo en modo sandbox (no se entregó).');

        Http::assertSent(fn (Request $peticion) => $peticion['mail_settings'] === ['sandbox_mode' => ['enable' => true]]);
    }
}
