<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ratings for the classic service verticals.
     *
     * `BusinessResource`, `AccommodationResource` and `ExperienceResource`
     * have always advertised `rating_avg` / `rating_count`, but the tables
     * had no such columns, so the resources reported a hardcoded 0 for
     * every record. These columns make the advertised contract real and
     * let the backoffice maintain the figures.
     *
     * Mirrors the marketplace shape (tours/restaurants/inns/bakeries use
     * `rating` + `reviews`); `rating_avg` is decimal(2,1) so it stays in
     * the 0.0 - 9.9 range, and `rating_count` defaults to 0 so the client
     * can tell "no reviews yet" from "rated 0".
     */
    public function up(): void
    {
        foreach (['businesses', 'accommodations', 'experiences'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->decimal('rating_avg', 2, 1)->default(0)
                    ->comment('Average rating, 0.0 - 9.9 (0 when unrated).');
                $blueprint->unsignedInteger('rating_count')->default(0)
                    ->comment('Number of published ratings behind rating_avg.');
            });
        }
    }

    public function down(): void
    {
        foreach (['businesses', 'accommodations', 'experiences'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn(['rating_avg', 'rating_count']);
            });
        }
    }
};
