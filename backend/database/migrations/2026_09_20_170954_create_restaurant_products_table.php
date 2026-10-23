<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Menu products of a restaurant (dishes, drinks, categories...). */
    public function up(): void
    {
        Schema::create('restaurant_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price')->default(0)->comment('COP');
            $table->string('image')->nullable();
            $table->string('category')->nullable()->comment('Platos fuertes | Bebidas | Postres | ...');
            $table->string('status')->default('active')
                ->comment('active | inactive');
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_products');
    }
};
