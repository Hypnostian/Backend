<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Entrada del log de integraciones. La crea el canal de log "integraciones"
 * (App\Logging\GuardarEnBaseDeDatos) y se consulta en la sección Logs.
 */
#[Fillable(['nivel', 'servicio', 'mensaje', 'contexto', 'created_at'])]
class Registro extends Model
{
    /** Un log no se edita: solo tiene fecha de creación. */
    public const UPDATED_AT = null;

    public const SERVICIOS = [
        'stripe' => 'Stripe',
        'webhook' => 'Webhook',
        'sendgrid' => 'SendGrid',
    ];

    public const NIVELES_DE_ERROR = ['error', 'critical', 'alert', 'emergency'];

    /** Nombres legibles de los datos que acompañan cada log, en el orden en que se muestran. */
    public const ETIQUETAS = [
        'evento' => 'Evento',
        'tipo' => 'Tipo',
        'payment_intent' => 'PaymentIntent',
        'monto' => 'Monto',
        'cliente' => 'Cliente',
        'tarjeta' => 'Tarjeta',
        'metodo_de_pago' => 'Método de pago',
        'destinatarios' => 'Destinatarios',
        'remitente' => 'Remitente',
        'asunto' => 'Asunto',
        'id_mensaje' => 'ID del mensaje',
        'codigo' => 'Código',
        'estado_http' => 'HTTP',
        'detalle' => 'Detalle',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contexto' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Errores y advertencias: lo que muestra el filtro "Problemas".
     */
    public function scopeProblemas(Builder $consulta): void
    {
        $consulta->whereIn('nivel', [...self::NIVELES_DE_ERROR, 'warning']);
    }

    public function esError(): bool
    {
        return in_array($this->nivel, self::NIVELES_DE_ERROR, true);
    }

    public function esAdvertencia(): bool
    {
        return $this->nivel === 'warning';
    }

    public function nombreNivel(): string
    {
        return match (true) {
            $this->esError() => 'Error',
            $this->esAdvertencia() => 'Advertencia',
            default => 'Info',
        };
    }

    public function nombreServicio(): string
    {
        return self::SERVICIOS[$this->servicio] ?? 'Aplicación';
    }

    /**
     * Datos del contexto con nombres legibles, listos para mostrar.
     *
     * @return array<string, string>
     */
    public function detalles(): array
    {
        // MySQL guarda el JSON con sus claves reordenadas: se muestran en el orden de ETIQUETAS
        $orden = array_flip(array_keys(self::ETIQUETAS));

        return collect($this->contexto ?? [])
            ->sortBy(fn ($valor, $clave) => $orden[$clave] ?? PHP_INT_MAX)
            ->mapWithKeys(fn ($valor, $clave) => [
                self::ETIQUETAS[$clave] ?? ucfirst(str_replace('_', ' ', (string) $clave)) => is_scalar($valor) ? (string) $valor : json_encode($valor, JSON_UNESCAPED_UNICODE),
            ])
            ->all();
    }
}
