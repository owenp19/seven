<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customers store minimal contact data without forcing an account.
     * Same phone/email across bookings is merged (unique index) so the
     * booking history is later available for reviews and favorites.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->uuid('guest_token')->nullable()->unique()->comment('Anonymous device identifier');
            $table->timestamps();

            $table->index(['phone', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
