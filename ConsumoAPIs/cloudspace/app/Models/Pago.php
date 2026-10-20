<?php

namespace App\Models;

use App\Support\Dinero;
use Database\Factories\PagoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'payment_intent_id',
    'estado',
    'monto',
    'moneda',
    'cliente_nombre',
    'cliente_email',
    'concepto',
    'tarjeta',
    'error',
    'stripe_event_id',
    'pagado_at',
    'correo_enviado_at',
])]
class Pago extends Model
{
    /** @use HasFactory<PagoFactory> */
    use HasFactory;

    public const EXITOSO = 'exitoso';

    public const FALLIDO = 'fallido';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'pagado_at' => 'datetime',
            'correo_enviado_at' => 'datetime',
        ];
    }

    /**
     * Monto con formato de moneda: "$ 45.000 COP".
     */
    protected function montoFormateado(): Attribute
    {
        return Attribute::get(fn () => Dinero::formatear($this->monto, $this->moneda));
    }

    public function fueExitoso(): bool
    {
        return $this->estado === self::EXITOSO;
    }
}
