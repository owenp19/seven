<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mensajes de una conversación del asistente.
     *
     * `role` es user|assistant. `payload` guarda los datos estructurados que
     * acompañaron al mensaje (recomendaciones/paquetes) para poder repintar la
     * conversación sin volver a llamar al modelo. Al borrar la conversación se
     * borran sus mensajes en cascada.
     */
    public function up(): void
    {
        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->char('conversation_id', 26);
            $table->string('role', 16);
            $table->text('content')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('assistant_conversations')->cascadeOnDelete();
            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_messages');
    }
};
