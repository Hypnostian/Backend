<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * La petición que llegó al webhook no viene de Stripe o está dañada
 * (sin cabecera Stripe-Signature, firma incorrecta o JSON inválido): 400 Bad Request.
 */
class WebhookInvalidoException extends RuntimeException
{
    //
}
