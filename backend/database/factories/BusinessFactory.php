<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'description' => $this->faker->paragraph(3),
            'address' => $this->faker->streetAddress(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'phone' => $this->faker->phoneNumber(),
            'whatsapp' => '+57'.$this->faker->numerify('301#######'),
            'email' => $this->faker->unique()->safeEmail(),
            'images' => [$this->faker->imageUrl(800, 600, 'food', true)],
            'opening_hours' => [
                'monday' => ['open' => '10:00', 'close' => '22:00'],
                'tuesday' => ['open' => '10:00', 'close' => '22:00'],
            ],
            'tags' => $this->faker->randomElements(['típico', 'mariscos', 'familiar'], 2),
            'features' => $this->faker->randomElements(['wifi', 'terraza', 'pet-friend'], 2),
            'is_verified' => $this->faker->boolean(70),
            'status' => 'approved',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }
}
