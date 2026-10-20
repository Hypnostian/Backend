<?php

namespace App\Support;

/**
 * Formato de montos para las vistas y los correos: "$ 45.000 COP", "US$ 15,00 USD".
 */
class Dinero
{
    public const SIMBOLOS = [
        'COP' => '$',
        'USD' => 'US$',
        'EUR' => '€',
        'MXN' => 'MX$',
    ];

    public static function formatear(float|int|string $monto, string $moneda): string
    {
        $monto = (float) $monto;
        $moneda = strtoupper($moneda);

        // Los pesos colombianos se muestran sin centavos cuando el valor es exacto
        $decimales = $moneda === 'COP' && floor($monto) === $monto ? 0 : 2;
        $numero = number_format($monto, $decimales, ',', '.');

        return trim((self::SIMBOLOS[$moneda] ?? '').' '.$numero.' '.$moneda);
    }
}
