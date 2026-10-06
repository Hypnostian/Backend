<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('platos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->index();
            // Número de punto flotante con precisión fija (2 decimales), ideal para dinero: 28500.00
            $table->decimal('precio', 10, 2);
            $table->text('descripcion');
            // Solo la ruta relativa dentro del disco "public", p. ej. platos/2026/09/hamburguesa-artesanal.png
            $table->string('imagen');
            $table->timestamps();
            // deleted_at: permite el "soft delete" (eliminación lógica) de los platos
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platos');
    }
};
