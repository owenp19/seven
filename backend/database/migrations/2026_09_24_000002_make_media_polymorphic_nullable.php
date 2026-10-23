<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `morphs('model')` creó las columnas NOT NULL, lo que impedía subir
     * archivos todavía no vinculados a una entidad (subir primero, asociar
     * después desde el panel). Las hacemos anulables.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('model_type')->nullable()->change();
            $table->unsignedBigInteger('model_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('model_type')->nullable(false)->change();
            $table->unsignedBigInteger('model_id')->nullable(false)->change();
        });
    }
};
