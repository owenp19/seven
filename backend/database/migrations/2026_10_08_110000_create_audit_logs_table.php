<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trazabilidad de acciones administrativas sensibles.
     *
     * Registra QUIÉN (user_id), QUÉ (action), SOBRE QUÉ (auditable_type/id),
     * CUÁNDO (created_at) y contexto no sensible (metadata JSON). No guarda
     * contraseñas, tokens Sanctum, tokens de acceso a reservas ni cabeceras
     * Authorization: la redacción la aplica AuditLogger.
     *
     * `user_id` es nullable para no perder el rastro si la cuenta se borra
     * (nullOnDelete) y para acciones sin actor humano (procesos).
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
