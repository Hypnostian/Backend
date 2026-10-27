<?php

namespace Tests\Unit;

use App\Enums\EstadoCita;
use PHPUnit\Framework\TestCase;

class EstadoCitaTest extends TestCase
{
    public function test_solo_permite_los_estados_del_requisito(): void
    {
        $this->assertSame(
            ['pendiente', 'confirmada', 'cancelada'],
            array_column(EstadoCita::cases(), 'value'),
        );

        $this->assertNull(EstadoCita::tryFrom('atendida'));
    }

    public function test_etiquetas_para_las_vistas(): void
    {
        $this->assertSame('Confirmada', EstadoCita::Confirmada->etiqueta());
        $this->assertSame('Canceladas', EstadoCita::Cancelada->plural());
    }
}
