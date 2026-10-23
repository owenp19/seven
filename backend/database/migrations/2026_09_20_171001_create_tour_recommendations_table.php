<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Things travelers should bring (sunscreen, camera, hydration...). */
    public function up(): void
    {
        Schema::create('tour_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->string('icon')->nullable()->comment('Ionicon name used by the app');
            $table->string('name');
            $table->timestamps();

            $table->index('tour_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_recommendations');
    }
};
