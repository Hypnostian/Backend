<?php

namespace Tests\Feature;

use App\Mail\ContactoMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactoFormTest extends TestCase
{
    public function test_contact_form_page_is_displayed(): void
    {
        $this->get('/contacto')
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_contact_form_shows_a_receipt_when_email_is_not_configured(): void
    {
        Storage::fake('public');
        config([
            'mail.default' => 'log',
            'mail.contact_to' => null,
        ]);

        $response = $this->post('/contacto', [
            'nombre' => 'Ana Ejemplo',
            'email' => 'ana@example.test',
            'mensaje' => 'Hola, quisiera recibir información.',
            'foto' => UploadedFile::fake()->create('foto.jpg', 10, 'image/jpeg'),
        ]);

        $response->assertRedirect('/contacto');
        $response->assertSessionHas('success', 'El formulario recibió tus datos. Aquí está el resumen de lo enviado.');
        $response->assertSessionHas('sent_data.nombre', 'Ana Ejemplo');
        $response->assertSessionHas('sent_data.email', 'ana@example.test');
        $response->assertSessionHas('sent_data.mensaje', 'Hola, quisiera recibir información.');

        $files = Storage::disk('public')->allFiles('fotos');
        $this->assertCount(1, $files);
        $response->assertSessionHas('sent_data.foto', $files[0]);
    }

    public function test_contact_form_sends_a_message_to_the_configured_recipient(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.contact_to' => 'contacto@example.test',
        ]);
        Mail::fake();

        $response = $this->post('/contacto', [
            'nombre' => 'Ana Ejemplo',
            'email' => 'ana@example.test',
            'mensaje' => 'Hola, quisiera recibir información.',
        ]);

        $response->assertRedirect('/contacto');
        $response->assertSessionHas('success');
        Mail::assertSent(ContactoMessage::class, function (ContactoMessage $mail): bool {
            return $mail->hasTo('contacto@example.test');
        });
    }

    public function test_contact_form_validation_messages_are_in_spanish(): void
    {
        $this->post('/contacto', ['email' => 'no-es-un-correo'])
            ->assertSessionHasErrors([
                'nombre' => 'El campo nombre es obligatorio.',
                'email' => 'Escribe un correo electrónico válido.',
                'mensaje' => 'El campo mensaje es obligatorio.',
            ]);
    }
}
