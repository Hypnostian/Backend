<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Los datos de un pago no cumplen las reglas del negocio o Stripe los rechazó
 * (moneda no habilitada, monto fuera de rango, referencia inexistente...).
 * Es un error del cliente: el controlador responde 400 Bad Request.
 */
class PagoInvalidoException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $campo = 'monto',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
