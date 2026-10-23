<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conversaciones del asistente turístico.
     *
     * El `id` es un ULID OPACO (no secuencial) para que un cliente no pueda
     * enumerar conversaciones ajenas adivinando el siguiente id. El
     * propietario se resuelve por `install_id` (misma identidad anónima que
     * usan reservas y comentarios): quien no aporta el install_id dueño de la
     * conversación recibe 404, igual que una reserva ajena.
     *
     * `expires_at` implementa la caducidad: pasado ese instante la
     * conversación deja de entregarse aunque la fila siga existiendo (la
     * limpieza física es opcional).
     */
    public function up(): void
    {
        Schema::create('assistant_conversations', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('install_id', 100)->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['install_id', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_conversations');
    }
};
