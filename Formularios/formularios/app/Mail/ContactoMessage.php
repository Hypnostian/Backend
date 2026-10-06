<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactoMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $email,
        public string $mensaje,
    ) {}

    public function build(): static
    {
        return $this->subject('Nuevo mensaje de contacto')
            ->replyTo($this->email, $this->nombre)
            ->html(
                '<p><strong>Nombre:</strong> '.e($this->nombre).'</p>'.
                '<p><strong>Correo:</strong> '.e($this->email).'</p>'.
                '<p><strong>Mensaje:</strong><br>'.nl2br(e($this->mensaje)).'</p>'
            );
    }
}
