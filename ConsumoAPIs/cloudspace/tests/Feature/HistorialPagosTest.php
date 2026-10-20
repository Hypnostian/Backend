<?php

namespace Tests\Feature;

use App\Models\Pago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistorialPagosTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_un_mensaje_cuando_no_hay_pagos(): void
    {
        $this->get('/pagos')
            ->assertOk()
            ->assertSee('Aún no hay pagos registrados');
    }

    public function test_lista_los_pagos_registrados_por_el_webhook(): void
    {
        Pago::factory()->create([
            'cliente_nombre' => 'Juan Sebastian Lizcano Jaimes',
            'concepto' => 'Sala de conferencias (2 horas)',
            'monto' => 120000,
            'moneda' => 'cop',
        ]);
        Pago::factory()->create(['monto' => 15, 'moneda' => 'usd', 'correo_enviado_at' => null]);
        Pago::factory()->fallido()->create();

        $this->get('/pagos')
            ->assertOk()
            ->assertSeeInOrder(['Pagos exitosos', '2', 'Pagos rechazados', '1'])
            ->assertSee('Juan Sebastian Lizcano Jaimes')
            ->assertSee('Sala de conferencias (2 horas)')
            ->assertSee('$ 120.000 COP')
            ->assertSee('US$ 15,00 USD')
            ->assertSee('Your card has insufficient funds.')
            ->assertSee('Rechazado')
            ->assertSee('No enviado');
    }

    public function test_suma_el_recaudo_por_moneda_solo_con_pagos_exitosos(): void
    {
        Pago::factory()->create(['monto' => 45000, 'moneda' => 'cop']);
        Pago::factory()->create(['monto' => 120000, 'moneda' => 'cop']);
        Pago::factory()->fallido()->create(['monto' => 999999, 'moneda' => 'cop']);

        $this->get('/pagos')
            ->assertOk()
            ->assertSeeInOrder(['Recaudo en COP', '$ 165.000 COP']);
    }

    public function test_pagina_de_diez_en_diez(): void
    {
        Pago::factory()->count(12)->create();

        $this->get('/pagos')
            ->assertOk()
            ->assertSee('Mostrando 1 a 10 de 12 pagos')
            ->assertSee('rel="next"', false);

        $this->get('/pagos?page=2')
            ->assertOk()
            ->assertSee('Mostrando 11 a 12 de 12 pagos');
    }
}
