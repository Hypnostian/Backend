<?php

namespace App\Logging;

use Illuminate\Support\Facades\Log;

/**
 * Escribe en el canal "integraciones": queda en storage/logs/integraciones-AAAA-MM-DD.log
 * y en la tabla registros, que se consulta en la sección Logs.
 *
 * La clase que usa el trait define la constante SERVICIO (stripe, webhook o sendgrid).
 */
trait EscribeLogs
{
    /**
     * @param  'info'|'warning'|'error'  $nivel
     * @param  array<string, mixed>  $contexto
     */
    protected function escribirLog(string $nivel, string $mensaje, array $contexto = [], ?string $servicio = null): void
    {
        Log::channel('integraciones')->log($nivel, $mensaje, [
            'servicio' => $servicio ?? self::SERVICIO,
            ...array_filter($contexto, fn ($valor) => $valor !== null && $valor !== ''),
        ]);
    }
}
