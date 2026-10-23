<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccommodationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_approved_accommodations(): void
    {
        $approved = Accommodation::factory()->create(['status' => 'approved']);
        Accommodation::factory()->create(['status' => 'inactive']);

        $this->getJson('/api/v1/accommodations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approved->id);
    }

    public function test_filters_by_guests_capacity(): void
    {
        Accommodation::factory()->create(['capacity' => 2, 'status' => 'approved']);
        Accommodation::factory()->create(['capacity' => 6, 'status' => 'approved']);

        $this->getJson('/api/v1/accommodations?guests=4')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.capacity', 6);
    }

    public function test_show_returns_not_found_for_inactive(): void
    {
        $accommodation = Accommodation::factory()->create(['status' => 'inactive']);

        $this->getJson("/api/v1/accommodations/{$accommodation->id}")
            ->assertNotFound();
    }

    public function test_returns_availability_for_accommodation(): void
    {
        $accommodation = Accommodation::factory()->create(['status' => 'approved', 'price_per_night' => 250000, 'capacity' => 4]);

        $this->getJson("/api/v1/accommodations/{$accommodation->id}/availability")
            ->assertOk()
            ->assertJsonPath('data.is_available', true)
            ->assertJsonPath('data.price_per_night', 250000);
    }
}
