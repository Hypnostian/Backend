<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use Illuminate\View\View;

class PagoController extends Controller
{
    /**
     * Historial de los pagos que Stripe notificó por el webhook.
     */
    public function index(): View
    {
        return view('pagos.index', [
            'pagos' => Pago::latest()->orderByDesc('id')->paginate(10),
            'exitosos' => Pago::where('estado', Pago::EXITOSO)->count(),
            'fallidos' => Pago::where('estado', Pago::FALLIDO)->count(),
            'recaudo' => Pago::where('estado', Pago::EXITOSO)
                ->selectRaw('moneda, SUM(monto) AS total')
                ->groupBy('moneda')
                ->orderBy('moneda')
                ->pluck('total', 'moneda'),
        ]);
    }
}
