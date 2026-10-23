<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Businesses represent every local venue listed on SEVEN:
     * restaurants, fast food, bakeries, raizal cuisine, experiences...
     * Each business belongs to one category and can optionally be an
     * official business partner (verified) of the platform.
     */
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('latitude', 20)->nullable();
            $table->string('longitude', 20)->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->json('images')->nullable()->comment('Ordered array of image URLs');
            $table->json('opening_hours')->nullable()->comment('Per-weekday schedule, e.g. {"monday":{"open":"10:00","close":"22:00"}}');
            $table->json('tags')->nullable();
            $table->json('features')->nullable()->comment('Amenities/features shown in the card');
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected', 'inactive'])->default('pending');
            $table->timestamps();

            $table->index(['status', 'is_verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
