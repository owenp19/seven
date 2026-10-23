<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use App\Models\BakeryProduct;
use App\Models\Business;
use App\Models\Category;
use App\Models\InnRoom;
use App\Models\Media;
use App\Models\Provider;
use App\Models\RestaurantProduct;
use App\Models\Tour;
use App\Models\TourImage;
use Database\Seeders\MarketplaceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression coverage for the defects found auditing the v1 surface.
 *
 * Every test here reproduces a bug that shipped: a 500 from an enum that
 * did not match the migration, a 422 on records the seeder itself created,
 * a `bookable.type` mislabelled in the panel, a catalog endpoint missing
 * from the contract, and a filter that silently ignored `?q=`.
 */
class RegressionTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        return ['X-Admin-Token' => config('api.admin.token')];
    }

    private function provider(string $type = 'tour_operator'): Provider
    {
        return Provider::create([
            'business_name' => 'Seven Excursions',
            'email' => 'excursions@seven.test',
            'type' => $type,
            'status' => 'approved',
        ]);
    }

    private function category(): Category
    {
        return Category::firstOrCreate(
            ['slug' => 'gastronomia'],
            ['name' => 'Gastronomía', 'is_active' => true],
        );
    }

    private function business(string $name = 'La Regatta', string $status = 'approved'): Business
    {
        return Business::create([
            'category_id' => $this->category()->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'status' => $status,
        ]);
    }

    private function tour(string $name = 'Johnny Cay', string $slug = 'johnny-cay'): Tour
    {
        return Tour::create([
            'provider_id' => $this->provider()->id,
            'name' => $name,
            'slug' => $slug,
            'status' => 'approved',
        ]);
    }

    public function test_business_status_accepts_every_value_the_column_allows(): void
    {
        // The column is an enum of pending|approved|rejected|inactive. The
        // admin rules used to advertise "suspended", which MySQL rejected
        // with a truncation error -> HTTP 500.
        $business = $this->business();

        foreach (['pending', 'approved', 'rejected', 'inactive'] as $status) {
            $this->withHeaders($this->adminHeaders())
                ->patchJson("/api/v1/admin/businesses/{$business->id}", ['status' => $status])
                ->assertOk()
                ->assertJsonPath('data.status', $status);
        }

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/businesses/{$business->id}", ['status' => 'suspended'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    public function test_every_classic_service_accepts_the_inactive_status(): void
    {
        // accommodations/experiences use a real enum, and the marketplace
        // tables document `inactive` in their column comment. All six admin
        // rules omitted it, so unpublishing a listing was impossible.
        $accommodation = Accommodation::create([
            'provider_id' => $this->provider('native_inn')->id,
            'name' => 'Posada Miss Trinie',
            'slug' => 'miss-trinie',
            'status' => 'approved',
        ]);

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/accommodations/{$accommodation->id}", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $tour = $this->tour();

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/tours/{$tour->id}", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_provider_type_accepts_bakery_so_seeded_rows_are_editable(): void
    {
        // MarketplaceSeeder creates three providers with type "bakery". The
        // admin rule did not list it, so those rows answered 422 on every
        // PATCH and could only be fixed with raw SQL.
        $provider = $this->provider('bakery');

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/providers/{$provider->id}", [
                'business_name' => 'Panadería Suzette',
            ])
            ->assertOk()
            ->assertJsonPath('data.business_name', 'Panadería Suzette');

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/providers/{$provider->id}", ['type' => 'tour_operator'])
            ->assertOk()
            ->assertJsonPath('data.type', 'tour_operator');
    }

    public function test_bakery_providers_seeded_by_the_marketplace_seeder_pass_validation(): void
    {
        $this->seed(MarketplaceSeeder::class);

        $bakeryProviders = Provider::where('type', 'bakery')->get();

        $this->assertNotEmpty($bakeryProviders, 'MarketplaceSeeder must create bakery providers.');

        foreach ($bakeryProviders as $provider) {
            $this->withHeaders($this->adminHeaders())
                ->patchJson("/api/v1/admin/providers/{$provider->id}", ['owner_name' => 'Admin'])
                ->assertOk();
        }
    }

    public function test_media_can_be_listed_and_inspected(): void
    {
        Storage::fake('public');

        $tour = $this->tour();

        $this->withHeaders($this->adminHeaders())
            ->post('/api/v1/admin/media', [
                'file' => UploadedFile::fake()->image('a.png'),
                'type' => 'gallery',
                'model_type' => Tour::class,
                'model_id' => $tour->id,
            ])
            ->assertCreated();

        $other = $this->withHeaders($this->adminHeaders())
            ->post('/api/v1/admin/media', [
                'file' => UploadedFile::fake()->image('b.png'),
                'type' => 'cover',
            ])
            ->assertCreated()
            ->json('data');

        $listing = $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/media')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 2);

        $this->assertCount(2, $listing->json('data'));

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/media?type=gallery')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.model_type', Tour::class);

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/media?model_type='.urlencode(Tour::class))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/media?model_id='.$tour->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($this->adminHeaders())
            ->getJson("/api/v1/admin/media/{$other['id']}")
            ->assertOk()
            ->assertJsonPath('data.id', $other['id'])
            ->assertJsonPath('data.type', 'cover');
    }

    public function test_media_can_be_attached_to_galleries_products_and_rooms(): void
    {
        Storage::fake('public');

        // The three sub-entities were missing from ALLOWED_MODELS, which
        // made it impossible to attach a photo to a gallery, a menu product
        // or a room through the API.
        foreach ([
            TourImage::class,
            RestaurantProduct::class,
            BakeryProduct::class,
            InnRoom::class,
        ] as $model) {
            $this->withHeaders($this->adminHeaders())
                ->post('/api/v1/admin/media', [
                    'file' => UploadedFile::fake()->image('x.png'),
                    'model_type' => $model,
                    'model_id' => 1,
                ])
                ->assertCreated()
                ->assertJsonPath('data.model_type', $model);
        }

        $this->assertSame(4, Media::count());
    }

    public function test_category_detail_reports_business_count_like_the_listing(): void
    {
        $category = $this->category();

        $this->business('La Regatta', 'approved');
        $this->business('Donde Francesca', 'pending');

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.0.business_count', 2);

        // `show()` skipped withCount(), so the same field was simply absent.
        $this->getJson('/api/v1/categories/gastronomia')
            ->assertOk()
            ->assertJsonPath('data.business_count', 2);
    }

    public function test_admin_panel_labels_tour_bookings_as_tours(): void
    {
        $tour = $this->tour();

        $created = $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'tour',
            'bookable_id' => $tour->id,
            'type' => 'experience',
            'start_at' => now()->addDay()->toDateTimeString(),
            'guests' => 2,
            'customer' => [
                'name' => 'Ana',
                'phone' => '+57 300 111 2222',
            ],
        ], ['X-Install-Id' => 'SEVEN-DEVICE-0001'])->assertCreated();

        $code = $created->json('data.booking_code');
        $token = $created->json('data.booking_access_token');

        // Both resources fell through to `default => business` because the
        // match() had no Tour arm, so the panel filed tours under businesses.
        $this->getJson("/api/v1/bookings/{$code}", ['X-Booking-Token' => $token])
            ->assertOk()
            ->assertJsonPath('data.bookable.type', 'tour');

        $this->withHeaders($this->adminHeaders())
            ->getJson("/api/v1/admin/bookings/{$code}")
            ->assertOk()
            ->assertJsonPath('data.bookable.type', 'tour');
    }

    public function test_ratings_are_persisted_and_exposed(): void
    {
        $business = $this->business();

        // These three resources advertised rating_avg/rating_count that no
        // column backed, so every record reported a hardcoded 0.
        $this->getJson("/api/v1/businesses/{$business->id}")
            ->assertOk()
            ->assertJsonPath('data.rating_avg', 0)
            ->assertJsonPath('data.rating_count', 0);

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/businesses/{$business->id}", [
                'rating_avg' => 4.5,
                'rating_count' => 12,
            ])
            ->assertOk()
            ->assertJsonPath('data.rating_avg', 4.5)
            ->assertJsonPath('data.rating_count', 12);

        $this->getJson("/api/v1/businesses/{$business->id}")
            ->assertOk()
            ->assertJsonPath('data.rating_avg', 4.5)
            ->assertJsonPath('data.rating_count', 12);
    }

    public function test_admin_listings_honour_the_q_filter(): void
    {
        $this->tour('Johnny Cay', 'johnny-cay');
        $this->tour('Acuario de San Luis', 'acuario');

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/tours?q=acuario')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'acuario');

        $this->withHeaders($this->adminHeaders())
            ->getJson('/api/v1/admin/tours?q=island')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_deleting_a_bookable_service_is_blocked_unless_forced(): void
    {
        $tour = $this->tour();

        $code = $this->postJson('/api/v1/bookings', [
            'bookable_type' => 'tour',
            'bookable_id' => $tour->id,
            'type' => 'experience',
            'start_at' => now()->addDay()->toDateTimeString(),
            'guests' => 2,
            'customer' => ['name' => 'Ana', 'phone' => '+57 300 111 2222'],
        ], ['X-Install-Id' => 'SEVEN-DEVICE-0001'])->assertCreated()->json('data.booking_code');

        // Hard-deleting left bookings pointing at a class that no longer had
        // a row, so the panel could not resolve them any more.
        $this->withHeaders($this->adminHeaders())
            ->deleteJson("/api/v1/admin/tours/{$tour->id}")
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['bookings']]);

        $this->assertDatabaseHas('tours', ['id' => $tour->id]);

        $this->withHeaders($this->adminHeaders())
            ->deleteJson("/api/v1/admin/tours/{$tour->id}?force=1")
            ->assertOk();

        $this->assertDatabaseMissing('tours', ['id' => $tour->id]);
        $this->assertDatabaseHas('bookings', ['booking_code' => $code]);
    }

    public function test_welcome_page_responds(): void
    {
        $this->get('/')->assertOk();
    }
}
