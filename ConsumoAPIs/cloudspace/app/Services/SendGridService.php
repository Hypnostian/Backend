<?php

namespace App\Services;

use App\Exceptions\ServicioExternoException;
use App\Logging\EscribeLogs;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Capa de servicio para el correo transaccional con la API REST v3 de SendGrid.
 *
 * Es la única clase que conoce SendGrid: arma el JSON de /mail/send, se autentica con
 * la API key (cabecera Authorization: Bearer) y traduce los errores del proveedor.
 * Cada envío, exitoso o fallido, queda en la sección Logs.
 */
class SendGridService
{
    use EscribeLogs;

    private const SERVICIO = 'sendgrid';

    public const URL_ENVIO = 'https://api.sendgrid.com/v3/mail/send';

    /**
     * Envía un correo a uno o varios destinatarios y devuelve el identificador que asigna SendGrid.
     *
     * @param  string|array<int, string>  $destinatarios
     *
     * @throws ServicioExternoException si falta configuración o SendGrid rechaza el envío
     */
    public function enviar(string|array $destinatarios, string $asunto, string $html, ?string $texto = null): string
    {
        $destinatarios = collect(Arr::wrap($destinatarios))
            ->map(fn ($correo) => trim((string) $correo))
            ->filter()
            ->unique()
            ->values();

        $contexto = ['destinatarios' => $destinatarios->implode(', '), 'asunto' => $asunto];

        $apiKey = config('services.sendgrid.key');
        $remitente = config('services.sendgrid.from.address');

        if (blank($apiKey)) {
            $this->fallar('Falta configurar SENDGRID_API_KEY en el archivo .env.', $contexto);
        }

        if (blank($remitente)) {
            $this->fallar('Falta configurar el remitente SENDGRID_FROM_ADDRESS en el archivo .env.', $contexto);
        }

        if ($destinatarios->isEmpty()) {
            $this->fallar('No hay destinatarios para el correo.', $contexto);
        }

        $cuerpo = [
            // Una "personalization" por destinatario: cada uno recibe su copia sin ver a los demás
            'personalizations' => $destinatarios
                ->map(fn (string $correo) => ['to' => [['email' => $correo]]])
                ->all(),
            'from' => array_filter([
                'email' => $remitente,
                'name' => config('services.sendgrid.from.name'),
            ]),
            'subject' => $asunto,
            // SendGrid exige que text/plain vaya antes que text/html
            'content' => [
                ['type' => 'text/plain', 'value' => $texto ?? $this->textoPlano($html)],
                ['type' => 'text/html', 'value' => $html],
            ],
        ];

        $sandbox = (bool) config('services.sendgrid.sandbox');

        if ($sandbox) {
            // Modo sandbox: SendGrid valida la petición y la credencial, pero no entrega el correo
            $cuerpo['mail_settings'] = ['sandbox_mode' => ['enable' => true]];
        }

        try {
            $respuesta = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(15)
                ->post(self::URL_ENVIO, $cuerpo);
        } catch (ConnectionException $e) {
            $this->fallar('No fue posible conectarse con SendGrid. Revisa la conexión a internet.', [...$contexto, 'detalle' => $e->getMessage()], previa: $e);
        }

        if ($respuesta->failed()) {
            $this->fallar($this->mensajeDeError($respuesta), [
                ...$contexto,
                'remitente' => $remitente,
                'estado_http' => $respuesta->status(),
                'detalle' => $respuesta->json('errors.0.message') ?? Str::limit($respuesta->body(), 300),
            ], $respuesta->status());
        }

        // 202 Accepted: SendGrid encoló el correo (en sandbox responde 200 sin identificador)
        $idMensaje = $respuesta->header('X-Message-Id') ?: 'sandbox';

        $this->escribirLog('info', $sandbox ? 'Correo validado por SendGrid en modo sandbox (no se entregó)' : 'Correo enviado con SendGrid', [
            ...$contexto,
            'estado_http' => $respuesta->status(),
            'id_mensaje' => $sandbox ? null : $idMensaje,
        ]);

        return $idMensaje;
    }

    /**
     * Deja el error en los logs y lo lanza para que lo maneje quien pidió el envío.
     *
     * @param  array<string, mixed>  $contexto
     *
     * @throws ServicioExternoException
     */
    private function fallar(string $mensaje, array $contexto, int $codigo = 0, ?\Throwable $previa = null): never
    {
        $this->escribirLog('error', $mensaje, $contexto);

        throw new ServicioExternoException($mensaje, $codigo, $previa);
    }

    /**
     * Traduce la respuesta de error de SendGrid a un mensaje claro en español.
     */
    private function mensajeDeError(Response $respuesta): string
    {
        $detalle = $respuesta->json('errors.0.message');

        $mensaje = match (true) {
            $respuesta->status() === 401 => 'SendGrid rechazó la API key. Revisa SENDGRID_API_KEY en el archivo .env.',
            $respuesta->status() === 403 && str_contains((string) $detalle, 'Sender Identity') => 'El remitente '.config('services.sendgrid.from.address').' no está verificado en SendGrid (Sender Identity).',
            $respuesta->status() === 403 => 'La API key de SendGrid no tiene permiso para enviar correos (Mail Send).',
            $respuesta->status() === 429 => 'Se superó el límite de envíos de SendGrid. Intenta de nuevo en unos minutos.',
            $respuesta->serverError() => 'SendGrid no está disponible en este momento. Intenta de nuevo más tarde.',
            default => 'SendGrid rechazó el correo.',
        };

        return $detalle ? "{$mensaje} (SendGrid {$respuesta->status()}: {$detalle})" : $mensaje;
    }

    /**
     * Versión en texto plano para los clientes de correo que no muestran HTML.
     */
    private function textoPlano(string $html): string
    {
        $texto = preg_replace(['/<(style|head)[^>]*>.*?<\/\1>/is', '/<(br|\/p|\/div|\/h[1-6]|\/tr|\/li)[^>]*>/i'], ['', "\n"], $html);
        $texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace(['/\r/', '/[ \t]+/', '/ *\n */', '/\n{3,}/'], ['', ' ', "\n", "\n\n"], $texto);

        return trim($texto);
    }
}
