<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Future-proof bookings for the marketplace model: a booking can be
     * attached to a provider and reference any service (tour, restaurant,
     * inn, experience) through `service_type` / `service_id` (morphs),
     * plus a payment status. The existing polymorphic `bookable` columns
     * stay untouched.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->nullableMorphs('service');
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_status')->default('pending')
                ->comment('pending | paid | failed | refunded | free')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropMorphs('service');
            $table->dropForeign(['provider_id']);
            $table->dropColumn('payment_status');
        });
    }
};
