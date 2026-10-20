<?php

namespace Tests\Feature;

use App\Exceptions\ServicioExternoException;
use App\Models\Registro;
use App\Services\SendGridService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SendGridServiceTest extends TestCase
{
    use RefreshDatabase;

    private SendGridService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->servicio = new SendGridService;
    }

    public function test_envia_el_json_de_la_api_v3_y_devuelve_el_id_del_mensaje(): void
    {
        Http::fake([SendGridService::URL_ENVIO => Http::response(null, 202, ['X-Message-Id' => 'msg_abc'])]);

        $id = $this->servicio->enviar('ana@cloudspace.test', 'Asunto', '<p>Hola</p>', 'Hola');

        $this->assertSame('msg_abc', $id);
        Http::assertSent(function (Request $peticion) {
            return $peticion->method() === 'POST'
                && $peticion->isJson()
                && $peticion->hasHeader('Authorization', 'Bearer SG.cloudspace-pruebas')
                && $peticion->data() === [
                    'personalizations' => [['to' => [['email' => 'ana@cloudspace.test']]]],
                    'from' => ['email' => 'pagos@cloudspace.test', 'name' => 'CloudSpace Solutions'],
                    'subject' => 'Asunto',
                    'content' => [
                        ['type' => 'text/plain', 'value' => 'Hola'],
                        ['type' => 'text/html', 'value' => '<p>Hola</p>'],
                    ],
                ];
        });
    }

    public function test_deja_en_los_logs_el_correo_enviado(): void
    {
        Http::fake([SendGridService::URL_ENVIO => Http::response(null, 202, ['X-Message-Id' => 'msg_abc'])]);

        $this->servicio->enviar(['jlizcano471@unab.edu.co'], 'Asunto', '<p>Hola</p>');

        $registro = Registro::sole();
        $this->assertSame('info', $registro->nivel);
        $this->assertSame('sendgrid', $registro->servicio);
        $this->assertSame('Correo enviado con SendGrid', $registro->mensaje);
        // MySQL ordena las claves de las columnas JSON: se compara sin importar el orden
        $this->assertEquals([
            'destinatarios' => 'jlizcano471@unab.edu.co',
            'asunto' => 'Asunto',
            'estado_http' => 202,
            'id_mensaje' => 'msg_abc',
        ], $registro->contexto);
    }

    public function test_deja_en_los_logs_el_error_que_devuelve_sendgrid(): void
    {
        Http::fake([SendGridService::URL_ENVIO => Http::response(['errors' => [['message' => 'The provided authorization grant is invalid, expired, or revoked']]], 401)]);

        try {
            $this->servicio->enviar('jlizcano471@unab.edu.co', 'Asunto', '<p>Hola</p>');
        } catch (ServicioExternoException) {
            // El error se valida en el log
        }

        $registro = Registro::sole();
        $this->assertSame('error', $registro->nivel);
        $this->assertSame('sendgrid', $registro->servicio);
        $this->assertStringStartsWith('SendGrid rechazó la API key.', $registro->mensaje);
        $this->assertSame(401, $registro->contexto['estado_http']);
        $this->assertSame('The provided authorization grant is invalid, expired, or revoked', $registro->contexto['detalle']);
        $this->assertSame('pagos@cloudspace.test', $registro->contexto['remitente']);
        $this->assertSame('jlizcano471@unab.edu.co', $registro->contexto['destinatarios']);
    }

    public function test_genera_la_version_en_texto_plano_desde_el_html(): void
    {
        Http::fake([SendGridService::URL_ENVIO => Http::response(null, 202)]);

        $this->servicio->enviar('ana@cloudspace.test', 'Asunto', '<style>p{color:red}</style><h1>Reserva</h1><p>Sala &amp; café<br>2 horas</p>');

        Http::assertSent(fn (Request $peticion) => $peticion['content'][0]['value'] === "Reserva\nSala & café\n2 horas");
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function erroresDeSendGrid(): array
    {
        return [
            'API key inválida' => [401, 'The provided authorization grant is invalid, expired, or revoked', 'SendGrid rechazó la API key'],
            'remitente sin verificar' => [403, 'The from address does not match a verified Sender Identity.', 'El remitente pagos@cloudspace.test no está verificado en SendGrid'],
            'API key sin permiso' => [403, 'access forbidden', 'no tiene permiso para enviar correos'],
            'límite de envíos' => [429, 'too many requests', 'Se superó el límite de envíos'],
            'falla de SendGrid' => [503, 'service unavailable', 'SendGrid no está disponible'],
            'petición rechazada' => [400, 'The to array is required for all personalization objects.', 'SendGrid rechazó el correo'],
        ];
    }

    #[DataProvider('erroresDeSendGrid')]
    public function test_traduce_los_errores_de_sendgrid(int $estado, string $detalle, string $esperado): void
    {
        Http::fake([SendGridService::URL_ENVIO => Http::response(['errors' => [['message' => $detalle]]], $estado)]);

        try {
            $this->servicio->enviar('ana@cloudspace.test', 'Asunto', '<p>Hola</p>');
            $this->fail("Se esperaba un error para la respuesta {$estado}.");
        } catch (ServicioExternoException $e) {
            $this->assertStringContainsString($esperado, $e->getMessage());
            $this->assertStringEndsWith("(SendGrid {$estado}: {$detalle})", $e->getMessage());
            $this->assertSame($estado, $e->getCode());
        }
    }

    public function test_informa_cuando_no_hay_conexion_con_sendgrid(): void
    {
        Http::fake([SendGridService::URL_ENVIO => Http::failedConnection()]);

        $this->expectException(ServicioExternoException::class);
        $this->expectExceptionMessage('No fue posible conectarse con SendGrid');

        $this->servicio->enviar('ana@cloudspace.test', 'Asunto', '<p>Hola</p>');
    }

    public function test_exige_la_api_key(): void
    {
        Http::fake();
        config(['services.sendgrid.key' => '']);

        $this->expectExceptionObject(new ServicioExternoException('Falta configurar SENDGRID_API_KEY en el archivo .env.'));

        $this->servicio->enviar('ana@cloudspace.test', 'Asunto', '<p>Hola</p>');
    }

    public function test_exige_el_remitente(): void
    {
        Http::fake();
        config(['services.sendgrid.from.address' => null]);

        $this->expectExceptionObject(new ServicioExternoException('Falta configurar el remitente SENDGRID_FROM_ADDRESS en el archivo .env.'));

        $this->servicio->enviar('ana@cloudspace.test', 'Asunto', '<p>Hola</p>');
    }

    public function test_exige_al_menos_un_destinatario(): void
    {
        Http::fake();

        try {
            $this->servicio->enviar(['', '  '], 'Asunto', '<p>Hola</p>');
            $this->fail('Se esperaba ServicioExternoException.');
        } catch (ServicioExternoException $e) {
            $this->assertSame('No hay destinatarios para el correo.', $e->getMessage());
        }

        Http::assertNothingSent();
    }
}
