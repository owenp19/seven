<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Products of a bakery (breads, pastries, desserts, drinks...). */
    public function up(): void
    {
        Schema::create('bakery_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bakery_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price')->default(0)->comment('COP');
            $table->string('image')->nullable();
            $table->string('category')->nullable()->comment('Panes | Repostería | Postres | Bebidas | ...');
            $table->string('status')->default('active')
                ->comment('active | inactive');
            $table->timestamps();

            $table->index(['bakery_id', 'status']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bakery_products');
    }
};
