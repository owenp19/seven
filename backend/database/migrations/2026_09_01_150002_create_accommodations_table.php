<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Accommodations are native inns (posadas) and lodging offers.
     * A booking references either a business, an accommodation
     * or an experience, so stays are BookableType polymorphic.
     */
    public function up(): void
    {
        Schema::create('accommodations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('capacity')->default(1)->comment('Max guests');
            $table->unsignedInteger('price_per_night')->default(0)->comment('Price in COP');
            $table->string('currency', 3)->default('COP');
            $table->string('address')->nullable();
            $table->string('latitude', 20)->nullable();
            $table->string('longitude', 20)->nullable();
            $table->json('images')->nullable();
            $table->json('included_services')->nullable()->comment('Breakfast, AC, Wi-Fi, etc.');
            $table->unsignedInteger('user_id')->nullable()->comment('Owner user, once accounts land in v2');
            $table->enum('status', ['pending', 'approved', 'rejected', 'inactive'])->default('pending');
            $table->timestamps();

            $table->index(['status', 'capacity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodations');
    }
};
