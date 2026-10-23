<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Polymorphic media repository. Stores any media file (logos,
     * covers, galleries, products, banners, promotions) with a type and
     * an ordering for display. Physical files live in storage/app/public.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('file_path');
            $table->string('type')->default('image')
                ->comment('logo | cover | gallery | product | banner | promotion | video');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['model_type', 'model_id', 'type', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
