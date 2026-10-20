<?php

namespace App\Events;

use App\Models\Pago;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Stripe confirmó un pago y ya quedó registrado en la base de datos.
 */
class PagoConfirmado
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public readonly Pago $pago)
    {
        //
    }
}
