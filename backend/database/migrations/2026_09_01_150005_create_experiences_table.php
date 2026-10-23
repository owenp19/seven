<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Experiences are bookable local activities (boat tours, cooking
     * classes, hiking, cultural walks...). They follow the same
     * bookable pattern as Business and Accommodation, so bookings
     * keep working through the polymorphic relation.
     */
    public function up(): void
    {
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('duration_minutes')->default(60)->comment('Suggested duration');
            $table->unsignedInteger('price_per_person')->default(0)->comment('Price in COP');
            $table->string('currency', 3)->default('COP');
            $table->unsignedInteger('max_capacity')->default(1)->comment('Max people per session');
            $table->string('address')->nullable();
            $table->string('latitude', 20)->nullable();
            $table->string('longitude', 20)->nullable();
            $table->json('includes')->nullable()->comment('What the experience includes');
            $table->json('images')->nullable();
            $table->unsignedInteger('user_id')->nullable()->comment('Owner user, once accounts land in v2');
            $table->enum('status', ['pending', 'approved', 'rejected', 'inactive'])->default('pending');
            $table->timestamps();

            $table->index(['status', 'max_capacity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
