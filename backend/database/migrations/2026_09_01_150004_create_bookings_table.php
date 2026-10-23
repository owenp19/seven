<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bookings handle gastronomic reservations, stays and experiences
     * through a polymorphic "bookable" relation. The booking code is a
     * short human-friendly reference that is shared with the business.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('booking_code', 20)->unique();
            $table->morphs('bookable'); // business, accommodation or experience
            $table->enum('type', ['gastronomic', 'accommodation', 'experience']);
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->unsignedInteger('guests')->default(1);
            $table->unsignedInteger('total_amount')->default(0)->comment('Amount in minor currency units (COP)');
            $table->string('currency', 3)->default('COP');
            $table->text('special_requests')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->timestamps();

            $table->index(['status', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
