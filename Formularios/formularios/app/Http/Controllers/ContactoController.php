<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactoRequest;
use App\Mail\ContactoMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class ContactoController extends Controller
{
    public function create(): View
    {
        return view('contacto');
    }

    public function send(ContactoRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $rutaarchivo = null;

        if ($request->hasFile('foto')) {
            $rutaarchivo = $request->file('foto')->store('fotos', 'public');

            if (! $rutaarchivo) {
                return back()->withInput()->with('error', 'No se pudo guardar la foto. Inténtalo de nuevo.');
            }
        }

        $recipient = config('mail.contact_to');
        $sentByEmail = false;

        if ($recipient && ! in_array(config('mail.default'), ['log', 'array'], true)) {
            try {
                $mail = new ContactoMessage(
                    $validated['nombre'],
                    $validated['email'],
                    $validated['mensaje'],
                );

                if ($request->hasFile('foto')) {
                    $photo = $request->file('foto');

                    $mail->attach($photo->getRealPath(), [
                        'as' => $photo->getClientOriginalName(),
                        'mime' => $photo->getMimeType(),
                    ]);
                }

                Mail::to($recipient)->send($mail);
                $sentByEmail = true;
            } catch (Throwable $exception) {
                report($exception);

                // El usuario volverá a enviar el formulario: no se deja la foto duplicada.
                if ($rutaarchivo) {
                    Storage::disk('public')->delete($rutaarchivo);
                }

                return back()->withInput()->with('error', 'No se pudo enviar el correo. Revisa la configuración SMTP.');
            }
        }

        $receipt = [
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'mensaje' => $validated['mensaje'],
            'foto' => $rutaarchivo,
        ];

        $status = $sentByEmail
            ? 'Tu mensaje fue enviado por correo.'
            : 'El formulario recibió tus datos. Aquí está el resumen de lo enviado.';

        return redirect()
            ->route('contacto.create')
            ->with('success', $status)
            ->with('sent_data', $receipt);
    }
}
