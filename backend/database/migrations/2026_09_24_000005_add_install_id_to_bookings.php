<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anonymous device fingerprint: lets SEVEN reach the person who made
     * the booking (status changes, confirms) without an account. Kept
     * server-side; it is never exposed through the public resource.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('install_id', 64)->nullable()
                ->comment('Anonymous device id (UUID) used to target push/local notifications without login.')
                ->after('provider_id');
            $table->index('install_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['install_id']);
            $table->dropColumn('install_id');
        });
    }
};
