<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Noticias editoriales de la isla.
 *
 * Hasta ahora la pantalla de Noticias no tenia entidad propia: la app armaba la
 * lista mezclando filas de `banners` y `promotions`. Eso obligaba a que una nota
 * editorial fuera un banner (que sale en el Home, en la posicion de otro) o una
 * promocion (que implica descuento). Con esta tabla cada noticia es una fila
 * propia y controlable sin efectos colaterales en otras pantallas.
 *
 * `kind` es la etiqueta editorial que muestra la tarjeta (Evento, Consejo,
 * Promocion). Es texto plano y no una tabla aparte porque el conjunto es
 * cerrado: se documenta en el modelo y ampliarlo es una migracion.
 *
 * `published_at` va separada de `created_at` para poder programar la
 * publicacion sin juggling de estados. Nula significa publicado ya; futura,
 * programado; pasada, publicado. `status` sigue existiendo para retirar una
 * nota y para dejar borradores.
 *
 * Los indices cubren las dos consultas reales: el listado general
 * (`status`, `published_at`) y el carrusel del Home (`is_featured`, `order`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable()->comment('Entradilla de la tarjeta');
            $table->longText('body')->nullable()->comment('Cuerpo completo de la nota');
            $table->string('image')->nullable()->comment('Portada');
            $table->string('kind', 20)->default('news')
                ->comment('news | event | tip | promotion');
            $table->foreignId('provider_id')->nullable()
                ->constrained('providers')->nullOnDelete()
                ->comment('Autor u operador, si es que lo hay');
            $table->string('link_url')->nullable()->comment('Destino del boton de accion');
            $table->string('status', 20)->default('published')
                ->comment('published | draft | archived');
            $table->dateTime('published_at')->nullable();
            $table->boolean('is_featured')->default(false)
                ->comment('Entra en el carrusel de novedades');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['status', 'published_at'], 'news_listable');
            $table->index(['is_featured', 'order'], 'news_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
