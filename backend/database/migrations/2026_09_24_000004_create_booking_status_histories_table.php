<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trazabilidad de cada solicitud: un registro por transición de estado
     * (creada → pending, pending → confirmed, pending → cancelled...).
     * changed_by indica el origen del cambio (admin | customer | system |
     * provider) y note deja contexto opcional (ej. motivo de cancelación).
     */
    public function up(): void
    {
        Schema::create('booking_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('changed_by')->nullable()->comment('admin | customer | system | provider');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_status_histories');
    }
};
