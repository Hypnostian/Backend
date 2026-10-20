<?php

namespace App\Http\Controllers;

use App\Exceptions\ServicioExternoException;
use App\Http\Requests\EnviarCorreoRequest;
use App\Services\SendGridService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmailController extends Controller
{
    /**
     * RF 3.2: formulario con datos de prueba para enviar correos con SendGrid.
     */
    public function create(): View
    {
        return view('email.create', [
            'prueba' => [
                'asunto' => 'Prueba de envío · CloudSpace Solutions',
                'mensaje' => "Hola:\n\nEste es un correo de prueba enviado desde el módulo de notificaciones de CloudSpace Solutions a través de la API REST de SendGrid.\n\nSi lo recibiste, la integración funciona correctamente.\n\nSaludos,\nEquipo CloudSpace",
            ],
            'remitente' => config('services.sendgrid.from.address'),
            'nombreRemitente' => config('services.sendgrid.from.name'),
            'configurado' => filled(config('services.sendgrid.key')) && filled(config('services.sendgrid.from.address')),
            'sandbox' => (bool) config('services.sendgrid.sandbox'),
            'maximo' => EnviarCorreoRequest::MAX_DESTINATARIOS,
        ]);
    }

    /**
     * RF 3.2: valida el formulario y delega el envío a SendGridService.
     */
    public function store(EnviarCorreoRequest $request, SendGridService $sendGrid): RedirectResponse
    {
        $destinatarios = $request->destinatarios();
        $asunto = $request->validated('asunto');
        $mensaje = $request->validated('mensaje');

        try {
            $idMensaje = $sendGrid->enviar(
                $destinatarios,
                $asunto,
                view('emails.mensaje', ['asunto' => $asunto, 'mensaje' => $mensaje])->render(),
                $mensaje,
            );
        } catch (ServicioExternoException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('email.create')->with('enviado', [
            'destinatarios' => $destinatarios,
            'id' => $idMensaje,
            'sandbox' => (bool) config('services.sendgrid.sandbox'),
        ]);
    }
}
