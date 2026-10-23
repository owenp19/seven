<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tours (boat trips, snorkeling, island tours) sold by tour operators.
     * Discoverable through GET /api/tours and /api/tours/{slug}.
     */
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable()->comment('Tours y experiencias');
            $table->string('location')->nullable();
            $table->string('duration')->nullable()->comment('e.g. 4 horas');
            $table->unsignedBigInteger('price')->default(0)->comment('COP');
            $table->decimal('rating', 2, 1)->default(0);
            $table->integer('reviews')->default(0);
            $table->string('cover_image')->nullable();
            $table->string('status')->default('pending')
                ->comment('pending | approved | rejected | inactive');
            $table->timestamps();

            $table->index('status');
            $table->index(['provider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
