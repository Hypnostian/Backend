<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Falla de un proveedor externo (Stripe o SendGrid): credenciales ausentes o rechazadas,
 * servicio caído o sin conexión. No es culpa del usuario: se responde 500 o se muestra el aviso.
 */
class ServicioExternoException extends RuntimeException
{
    //
}
