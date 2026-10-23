<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Native inns (posadas nativas). Discoverable through
     * GET /api/inns and /api/inns/{slug}.
     */
    public function up(): void
    {
        Schema::create('inns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('location')->nullable();
            $table->unsignedBigInteger('price')->default(0)->comment('per night COP');
            $table->json('services')->nullable()->comment('Desayuno, Wi-Fi, A/C...');
            $table->boolean('availability')->default(true);
            $table->decimal('rating', 2, 1)->default(0);
            $table->string('status')->default('pending')
                ->comment('pending | approved | rejected | inactive');
            $table->timestamps();

            $table->index('status');
            $table->index(['provider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inns');
    }
};
