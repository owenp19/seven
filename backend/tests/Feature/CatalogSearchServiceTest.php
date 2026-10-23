<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\Inn;
use App\Models\Restaurant;
use App\Models\Tour;
use App\Services\Catalog\CatalogItemDTO;
use App\Services\Catalog\CatalogSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): CatalogSearchService
    {
        return $this->app->make(CatalogSearchService::class);
    }

    public function test_it_only_returns_approved_records(): void
    {
        Tour::create(['name' => 'Aprobado', 'slug' => 'aprobado', 'price' => 100000, 'status' => 'approved']);
        Tour::create(['name' => 'Pendiente', 'slug' => 'pendiente', 'price' => 100000, 'status' => 'pending']);

        $items = $this->service()->search(['types' => ['tour']]);

        $this->assertCount(1, $items);
        $this->assertSame('Aprobado', $items[0]->name);
    }

    public function test_it_assigns_correct_price_units_per_vertical(): void
    {
        Tour::create(['name' => 'Tour', 'slug' => 'tour', 'price' => 80000, 'status' => 'approved']);
        Experience::factory()->create(['status' => 'approved', 'price_per_person' => 90000]);
        Inn::create(['name' => 'Posada', 'slug' => 'posada', 'price' => 150000, 'status' => 'approved']);

        $items = collect($this->service()->search(['types' => ['tour', 'experience', 'inn']]))
            ->keyBy('type');

        $this->assertSame(CatalogItemDTO::UNIT_PER_PERSON, $items['tour']->priceUnit);
        $this->assertSame(CatalogItemDTO::UNIT_PER_PERSON, $items['experience']->priceUnit);
        $this->assertSame(CatalogItemDTO::UNIT_PER_NIGHT, $items['inn']->priceUnit);
    }

    public function test_restaurants_have_no_scalar_price(): void
    {
        Restaurant::create([
            'name' => 'Comida', 'slug' => 'comida', 'price_range' => '$$', 'status' => 'approved',
        ]);

        $items = $this->service()->search(['types' => ['restaurant']]);

        $this->assertCount(1, $items);
        $this->assertNull($items[0]->price);
        $this->assertFalse($items[0]->hasPrice());
    }

    public function test_detail_resolves_by_slug_and_rejects_unknown(): void
    {
        Tour::create(['name' => 'Tour X', 'slug' => 'tour-x', 'price' => 100000, 'status' => 'approved']);

        $this->assertNotNull($this->service()->detail('tour', 'tour-x'));
        $this->assertNull($this->service()->detail('tour', 'no-existe'));
        $this->assertNull($this->service()->detail('banana', 'tour-x'));
    }
}
