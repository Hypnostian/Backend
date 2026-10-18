<?php

namespace Tests\Unit;

use App\Models\Evento;
use App\Models\User;
use App\Policies\EventoPolicy;
use PHPUnit\Framework\TestCase;

class EventoPolicyTest extends TestCase
{
    public function test_permite_actualizar_al_organizador_propietario(): void
    {
        $organizador = (new User)->forceFill(['id' => 7, 'role' => 'organizador']);
        $evento = (new Evento)->forceFill(['user_id' => 7]);

        $this->assertTrue((new EventoPolicy)->update($organizador, $evento)->allowed());
    }

    public function test_rechaza_a_cualquier_usuario_que_no_sea_el_propietario(): void
    {
        $evento = (new Evento)->forceFill(['user_id' => 7]);
        $otroOrganizador = (new User)->forceFill(['id' => 8, 'role' => 'organizador']);
        $admin = (new User)->forceFill(['id' => 1, 'role' => 'admin']);

        $respuesta = (new EventoPolicy)->update($otroOrganizador, $evento);

        $this->assertTrue($respuesta->denied());
        $this->assertSame('Solo el organizador propietario puede modificar este evento.', $respuesta->message());
        $this->assertTrue((new EventoPolicy)->update($admin, $evento)->denied());
    }

    public function test_la_comparacion_estricta_no_falla_si_el_user_id_llega_como_texto(): void
    {
        // El cast del modelo convierte user_id a entero antes de compararlo con ===
        $organizador = (new User)->forceFill(['id' => 7]);
        $evento = (new Evento)->forceFill(['user_id' => '7']);

        $this->assertTrue((new EventoPolicy)->update($organizador, $evento)->allowed());
    }
}
