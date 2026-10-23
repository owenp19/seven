<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moderación de noticias (Parte 3).
 *
 * Una noticia creada por el panel del proveedor no debe salir a la app por
 * su cuenta: el default pasa de `published` a `draft` y el backoffice es
 * quien la publica. Las filas existentes se conservan tal cual (notas ya
 * publicadas siguen publicadas); el cambio solo afecta a las altas futuras.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('status', 20)
                ->default('draft')
                ->comment('published | draft | archived')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('status', 20)
                ->default('published')
                ->comment('published | draft | archived')
                ->change();
        });
    }
};
