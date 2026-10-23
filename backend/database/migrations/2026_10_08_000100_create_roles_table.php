<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de roles de SEVEN.
     *
     * Dos fases:
     *  - Activos: SUPER_ADMIN, ADMIN_SEVEN (backoffice actual).
     *  - Futuros: PROVEEDOR_TURISTICO, EMPLEADO_PROVEEDOR, EDITOR.
     *
     * Los roles se asignan por la pivote `role_user`; la columna string
     * `users.role` se conserva por compatibilidad con datos/clients viejos
     * y no se usa para autorizar.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('label', 120);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
