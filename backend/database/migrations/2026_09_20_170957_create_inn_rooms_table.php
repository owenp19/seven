<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Rooms available in a native inn. */
    public function up(): void
    {
        Schema::create('inn_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inn_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price')->default(0)->comment('COP per night');
            $table->string('image')->nullable();
            $table->unsignedInteger('capacity')->default(2);
            $table->timestamps();

            $table->index('inn_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inn_rooms');
    }
};
