<?php

namespace Tests\Feature;

use App\Models\Experience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExperiencesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_approved_experiences(): void
    {
        $approved = Experience::factory()->create(['status' => 'approved']);
        Experience::factory()->create(['status' => 'pending']);

        $this->getJson('/api/v1/experiences')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approved->id);
    }

    public function test_filters_by_guests_capacity(): void
    {
        Experience::factory()->create(['max_capacity' => 2, 'status' => 'approved']);
        Experience::factory()->create(['max_capacity' => 12, 'status' => 'approved']);

        $this->getJson('/api/v1/experiences?guests=6')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.max_capacity', 12);
    }

    public function test_show_returns_not_found_for_pending(): void
    {
        $experience = Experience::factory()->create(['status' => 'pending']);

        $this->getJson("/api/v1/experiences/{$experience->id}")
            ->assertNotFound();
    }

    public function test_returns_availability_for_experience(): void
    {
        $experience = Experience::factory()->create(['status' => 'approved', 'price_per_person' => 120000, 'max_capacity' => 8]);

        $this->getJson("/api/v1/experiences/{$experience->id}/availability")
            ->assertOk()
            ->assertJsonPath('data.is_available', true)
            ->assertJsonPath('data.price_per_person', 120000)
            ->assertJsonPath('data.max_capacity', 8);
    }
}
