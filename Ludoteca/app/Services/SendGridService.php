<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class SendGridService
{
    /**
     * Envía un correo electrónico utilizando la API REST v3 de SendGrid.
     */
    public function sendEmail(string $to, string $subject, string $content): bool
    {
        $response = Http::withToken(config('services.sendgrid.key'))
            ->post('https://api.sendgrid.com/v3/mail/send', [
                'personalizations' => [
                    [
                        'to' => [
                            ['email' => $to]
                        ]
                    ]
                ],
                'from' => [
                    'email' => config('mail.from.address'),
                    'name' => config('mail.from.name')
                ],
                'subject' => $subject,
                'content' => [
                    [
                        'type' => 'text/html',
                        'value' => $content
                    ]
                ]
            ]);

        return $response->successful(); // Devuelve true si el correo se envió correctamente, false en caso contrario

        /*return [
            'status' => $response->status(),
            'body' => $response->body(),
            'json' => $response->json(),
            'successful' => $response->successful(),
        ];*/
    }
}
