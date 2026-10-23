<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Bakery;
use App\Models\Banner;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Category;
use App\Models\Experience;
use App\Models\Inn;
use App\Models\Promotion;
use App\Models\Restaurant;
use App\Models\Tour;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * WelcomeController
 *
 * Serves the branded API portal at `/` (replaces the stock Laravel page).
 * The view is fully themed with the SEVEN design system and shows live,
 * cached counts of every public resource so developers see, at a glance,
 * what the API exposes.
 */
class WelcomeController extends Controller
{
    public function index(): View
    {
        return view('welcome', [
            'api' => [
                'name' => 'SEVEN API',
                'prefix' => config('api.prefix'),
                'version' => config('api.versions')[0] ?? 'v1',
                'status' => 'ok',
                'root' => url('/api/'.(config('api.versions')[0] ?? 'v1')),
                'docs_ui' => url('/api/documentation'),
                'docs_json' => url('/docs'),
            ],
            'runtime' => [
                'framework' => config('app.env') === 'production' ? 'Laravel' : 'Laravel '.app()->version(),
                'php' => config('app.env') === 'production' ? 'PHP' : 'PHP '.PHP_VERSION,
                'env' => config('app.env'),
            ],
            'stats' => $this->stats(),
        ]);
    }

    /**
     * Live counters per public resource, cached 60s to avoid hammering
     * the database on anonymous page views.
     */
    private function stats(): array
    {
        return Cache::remember('welcome.stats', 60, function () {
            return [
                'categories' => ['count' => Category::query()->active()->count(), 'color' => 'tide'],
                'tours' => ['count' => Tour::query()->approved()->count(), 'color' => 'blue'],
                'restaurants' => ['count' => Restaurant::query()->approved()->count(), 'color' => 'coral'],
                'inns' => ['count' => Inn::query()->approved()->count(), 'color' => 'green'],
                'bakeries' => ['count' => Bakery::query()->approved()->count(), 'color' => 'gold'],
                'banners' => ['count' => Banner::query()->active()->count(), 'color' => 'lagoon'],
                'promotions' => ['count' => Promotion::query()->active()->count(), 'color' => 'coral'],
                'businesses' => ['count' => Business::query()->approved()->count(), 'color' => 'blue'],
                'accommodations' => ['count' => Accommodation::query()->approved()->count(), 'color' => 'green'],
                'experiences' => ['count' => Experience::query()->approved()->count(), 'color' => 'lagoon'],
                'bookings' => ['count' => Booking::query()->count(), 'color' => 'tide'],
            ];
        });
    }
}
