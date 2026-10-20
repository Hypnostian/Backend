<?php

namespace Tests\Support;

use Stripe\HttpClient\ClientInterface;

/**
 * Cliente HTTP falso para el SDK de Stripe: responde con datos preparados y guarda cada
 * petición, así las pruebas revisan lo que el SDK enviaría sin conectarse a Stripe.
 */
class StripeHttpFalso implements ClientInterface
{
    /**
     * @var array<int, array{metodo: string, url: string, parametros: array<string, mixed>}>
     */
    public array $peticiones = [];

    /**
     * @param  array<int, array{0: int, 1: array<string, mixed>}>  $respuestas  cola de [código HTTP, cuerpo JSON]
     */
    public function __construct(private array $respuestas = [])
    {
        //
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $this->peticiones[] = ['metodo' => $method, 'url' => $absUrl, 'parametros' => $params];

        [$codigo, $cuerpo] = array_shift($this->respuestas)
            ?? [500, ['error' => ['type' => 'api_error', 'message' => 'La prueba no preparó una respuesta.']]];

        return [json_encode($cuerpo), $codigo, ['request-id' => 'req_prueba']];
    }
}
