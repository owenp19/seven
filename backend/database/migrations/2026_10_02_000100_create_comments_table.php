<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comentarios públicos de tours, restaurantes y posadas.
 *
 * La identidad no es el usuario: la app no tiene login, así que cada
 * comentario se atribuye al `install_id` (UUID por dispositivo) que ya usa
 * `bookings`. El índice único de tres columnas es lo que impide que un
 * dispositivo opine dos veces sobre el mismo destino; se confía en la base y
 * no en un `first()` en PHP porque dos peticiones simultáneas (doble toque en
 * "Publicar") pasarían el filtro a la vez.
 *
 * `status` va en `published` por defecto para que la publicación sea
 * automática, y el filtro de spam solo degrada a `pending` lo sospechoso:
 * un viajero legítimo nunca debe quedarse sin voz por escribir mal.
 *
 * Se usa `morphs('commentable')` para no atar la tabla a un catálogo
 * concreto: el mismo día que se quiera opinar sobre un negocio, solo hay que
 * declarar la relación en el modelo, sin otra migración.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->string('install_id', 64)->comment('UUID del dispositivo, no se expone en la API');
            $table->string('author_name', 80);
            $table->unsignedTinyInteger('rating')->nullable()->comment('1 a 5, opcional');
            $table->text('body');
            $table->string('status', 20)->default('published')
                ->comment('published | pending | hidden');
            $table->timestamps();

            $table->unique(
                ['commentable_type', 'commentable_id', 'install_id'],
                'comments_one_comment_per_device',
            );
            $table->index(
                ['commentable_type', 'commentable_id', 'status', 'created_at'],
                'comments_listable',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
