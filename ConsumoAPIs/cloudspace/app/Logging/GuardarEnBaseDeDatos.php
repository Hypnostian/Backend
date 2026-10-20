<?php

namespace App\Logging;

use App\Models\Registro;
use Illuminate\Support\Arr;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Throwable;

/**
 * Handler de Monolog para el canal "integraciones": guarda cada log en la tabla
 * registros para mostrarlo en la sección Logs de la aplicación.
 */
class GuardarEnBaseDeDatos extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            Registro::create([
                'nivel' => $record->level->toPsrLogLevel(),
                'servicio' => $record->context['servicio'] ?? 'aplicacion',
                'mensaje' => $record->message,
                'contexto' => Arr::except($record->context, ['servicio', 'exception']) ?: null,
                'created_at' => $record->datetime,
            ]);
        } catch (Throwable) {
            // Sin base de datos el log igual queda en storage/logs/integraciones-*.log
        }
    }
}
