<?php

namespace Tests\Unit;

use App\Support\Dinero;
use PHPUnit\Framework\TestCase;

class DineroTest extends TestCase
{
    public function test_los_pesos_colombianos_se_muestran_sin_centavos_cuando_el_valor_es_exacto(): void
    {
        $this->assertSame('$ 45.000 COP', Dinero::formatear(45000, 'cop'));
        $this->assertSame('$ 1.250.000 COP', Dinero::formatear('1250000.00', 'COP'));
    }

    public function test_los_pesos_con_centavos_conservan_dos_decimales(): void
    {
        $this->assertSame('$ 45.000,50 COP', Dinero::formatear(45000.5, 'cop'));
    }

    public function test_las_demas_monedas_usan_dos_decimales_y_su_simbolo(): void
    {
        $this->assertSame('US$ 15,00 USD', Dinero::formatear(15, 'usd'));
        $this->assertSame('€ 0,50 EUR', Dinero::formatear(0.5, 'eur'));
        $this->assertSame('MX$ 1.234,56 MXN', Dinero::formatear(1234.56, 'mxn'));
    }

    public function test_una_moneda_sin_simbolo_muestra_solo_el_codigo(): void
    {
        $this->assertSame('99,90 GBP', Dinero::formatear(99.9, 'gbp'));
    }
}
