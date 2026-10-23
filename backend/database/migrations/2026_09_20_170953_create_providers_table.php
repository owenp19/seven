<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Providers are the businesses behind the marketplace services:
     * tour operators, restaurants, native inns and experience providers.
     */
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_name');
            $table->string('owner_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('logo')->nullable();
            $table->string('type')->default('tour_operator')
                ->comment('tour_operator | restaurant | native_inn | experience_provider');
            $table->string('status')->default('pending')
                ->comment('pending | approved | rejected | inactive');
            $table->string('verification_status')->default('unverified')
                ->comment('unverified | verified | suspended');
            $table->timestamps();

            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
