<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend categories with the marketplace naming (`order`, `status`)
     * while keeping the legacy `sort_order` / `is_active` columns
     * populated so no existing feature breaks.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('order')->default(0)->after('sort_order');
            $table->string('status')->default('active')->after('is_active');
        });

        // Backfill from the legacy columns (portable across MySQL and SQLite).
        DB::table('categories')->update([
            'order' => DB::raw('sort_order'),
        ]);
        DB::table('categories')->where('is_active', 1)->update(['status' => 'active']);
        DB::table('categories')->where('is_active', 0)->update(['status' => 'inactive']);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['order', 'status']);
        });
    }
};
