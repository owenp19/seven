<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hardening for concurrency-sensitive queries:
     *
     * 1. `customers.phone` becomes UNIQUE — the booking service wraps
     *    customer resolution in a transaction with row locks and uses this
     *    constraint as the final backstop against duplicates. Existing
     *    duplicates (from the old race) are merged defensively first.
     * 2. `bookings` gains a composite index on (bookable_type, bookable_id,
     *    status, start_at) so the availability/oversell checks (which run
     *    with `start_at BETWEEN ...` — sargable — plus `lockForUpdate`)
     *    are served by the index and lock exactly the scanned rows/gaps.
     */
    public function up(): void
    {
        // 1a. Reassign bookings of duplicate customers to the kept row,
        //     then remove the duplicates (cross-driver: works on MySQL
        //     and SQLite, which powers the test suite).
        $duplicatedPhones = DB::table('customers')
            ->select('phone')
            ->whereNotNull('phone')
            ->where('phone', '<>', '')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone');

        foreach ($duplicatedPhones as $phone) {
            $ids = DB::table('customers')
                ->where('phone', $phone)
                ->orderBy('id')
                ->pluck('id');

            $keepId = $ids->shift();

            if ($keepId === null || $ids->isEmpty()) {
                continue;
            }

            foreach ($ids as $duplicateId) {
                DB::table('bookings')->where('customer_id', $duplicateId)->update(['customer_id' => $keepId]);
                DB::table('customers')->where('id', $duplicateId)->delete();
            }
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_phone_email_index');
            $table->unique('phone');       // MySQL/SQLite: permite múltiples NULL
            $table->index('email', 'customers_email_index');
        });

        // 2. Availability / oversell guard: bookable + status + window.
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['bookable_type', 'bookable_id', 'status', 'start_at'], 'bookings_bookable_status_start_idx');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_phone_unique');
            $table->dropIndex('customers_email_index');
            $table->index(['phone', 'email'], 'customers_phone_email_index');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_bookable_status_start_idx');
        });
    }
};
