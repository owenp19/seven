<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moderación de promociones (Parte 3).
 *
 * Una promoción creada por el panel del proveedor no debe salir a la app
 * por su cuenta: el default pasa de `active` a `pending` y el backoffice
 * es quien la publica. Las filas existentes se conservan tal cual
 * (material ya publicado sigue publicado); el cambio solo afecta a las
 * altas futuras.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('status')
                ->default('pending')
                ->comment('pending | active | inactive')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('status')
                ->default('active')
                ->comment('active | inactive')
                ->change();
        });
    }
};
