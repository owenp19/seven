<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permisos granulares (ej. `catalog.manage`, `bookings.manage`).
     *
     * Hoy la autorización del backoffice es por rol; la tabla queda lista
     * para que rutas futuras (panel de proveedor, edición de contenido)
     * puedan restringir por permiso sin cambiar el esquema.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('label', 120);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
