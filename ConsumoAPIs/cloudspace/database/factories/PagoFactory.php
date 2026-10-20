<?php

namespace Database\Factories;

use App\Models\Pago;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_intent_id' => 'pi_'.Str::random(24),
            'estado' => Pago::EXITOSO,
            'monto' => fake()->randomElement([45000, 120000, 180000]),
            'moneda' => 'cop',
            'cliente_nombre' => 'Juan Sebastian Lizcano Jaimes',
            'cliente_email' => 'jlizcano471@unab.edu.co',
            'concepto' => fake()->randomElement(['Puesto flexible (1 día)', 'Oficina privada (1 día)', 'Sala de conferencias (2 horas)']),
            'tarjeta' => 'Visa •••• 4242',
            'error' => null,
            'stripe_event_id' => 'evt_'.Str::random(24),
            'pagado_at' => now(),
            'correo_enviado_at' => now(),
        ];
    }

    public function fallido(): static
    {
        return $this->state(fn () => [
            'estado' => Pago::FALLIDO,
            'tarjeta' => 'Visa •••• 9995',
            'error' => 'Your card has insufficient funds.',
            'pagado_at' => null,
            'correo_enviado_at' => null,
        ]);
    }
}
