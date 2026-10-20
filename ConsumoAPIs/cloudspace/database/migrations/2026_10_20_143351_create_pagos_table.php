<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pagos que confirma Stripe por el webhook (RF 2.1).
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            // Un registro por PaymentIntent: si Stripe reenvía el evento no se duplica el pago
            $table->string('payment_intent_id')->unique();
            $table->enum('estado', ['exitoso', 'fallido'])->index();
            $table->decimal('monto', 12, 2);
            $table->char('moneda', 3);
            $table->string('cliente_nombre', 100)->nullable();
            $table->string('cliente_email', 150)->nullable();
            $table->string('concepto', 150)->nullable();
            $table->string('tarjeta', 60)->nullable();
            $table->string('error')->nullable();
            $table->string('stripe_event_id')->nullable();
            $table->timestamp('pagado_at')->nullable();
            $table->timestamp('correo_enviado_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
