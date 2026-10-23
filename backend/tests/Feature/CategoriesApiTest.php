<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_active_categories(): void
    {
        Category::create(['slug' => 'restaurantes', 'name' => 'Restaurantes', 'icon' => 'restaurant', 'sort_order' => 1, 'is_active' => true]);
        Category::create(['slug' => 'oculta', 'name' => 'Oculta', 'icon' => 'eye', 'sort_order' => 2, 'is_active' => false]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'restaurantes');
    }

    public function test_shows_category_by_slug(): void
    {
        $category = Category::create(['slug' => 'cocina-raizal', 'name' => 'Cocina raizal', 'icon' => 'fish', 'sort_order' => 1, 'is_active' => true]);

        $this->getJson('/api/v1/categories/cocina-raizal')
            ->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', 'Cocina raizal');
    }

    public function test_returns_404_for_missing_category(): void
    {
        $this->getJson('/api/v1/categories/no-existe')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
