<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessesApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $slug): Category
    {
        return Category::create(['slug' => $slug, 'name' => ucfirst($slug), 'icon' => 'star', 'sort_order' => 1, 'is_active' => true]);
    }

    public function test_lists_only_approved_businesses(): void
    {
        $approved = Business::factory()->create(['status' => 'approved']);
        Business::factory()->create(['status' => 'pending']);

        $this->getJson('/api/v1/businesses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approved->id);
    }

    public function test_filters_businesses_by_category(): void
    {
        $restaurantes = $this->category('restaurantes');
        $other = $this->category('panaderias');

        Business::factory()->create(['category_id' => $restaurantes->id]);
        Business::factory()->create(['category_id' => $other->id]);

        $this->getJson('/api/v1/businesses?category=restaurantes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category.slug', 'restaurantes');
    }

    public function test_searches_businesses_by_name(): void
    {
        Business::factory()->create(['name' => 'Restaurante El Rincón', 'status' => 'approved']);
        Business::factory()->create(['name' => 'Panadería Dulce', 'status' => 'approved']);

        $this->getJson('/api/v1/businesses?q=Rincón')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Restaurante El Rincón');
    }

    public function test_returns_404_when_business_is_not_approved(): void
    {
        $business = Business::factory()->create(['status' => 'pending']);

        $this->getJson("/api/v1/businesses/{$business->id}")
            ->assertNotFound();
    }

    public function test_returns_availability_for_business(): void
    {
        $business = Business::factory()->create([
            'status' => 'approved',
            'opening_hours' => ['monday' => ['open' => '10:00', 'close' => '22:00']],
        ]);

        $date = now()->next('Monday')->toDateString();

        $this->getJson("/api/v1/businesses/{$business->id}/availability?date={$date}")
            ->assertOk()
            ->assertJsonPath('data.is_open', true)
            ->assertJsonPath('data.opening_hours.open', '10:00');
    }
}
