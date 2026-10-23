<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(['Restaurantes', 'Panaderías', 'Comida rápida', 'Cocina raizal', 'Posadas nativas', 'Experiencias']);

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'icon' => $this->faker->randomElement(['restaurant-outline', 'pizza-outline', 'fast-food-outline', 'fish-outline', 'home-outline', 'compass-outline']),
            'image' => null,
            'description' => $this->faker->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
