<?php

namespace Database\Factories;

use App\Models\Accommodation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Accommodation>
 */
class AccommodationFactory extends Factory
{
    protected $model = Accommodation::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'description' => $this->faker->paragraph(3),
            'capacity' => $this->faker->numberBetween(1, 6),
            'price_per_night' => $this->faker->numberBetween(120000, 650000),
            'currency' => 'COP',
            'address' => $this->faker->streetAddress(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'images' => [$this->faker->imageUrl(800, 600, 'lodging', true)],
            'included_services' => $this->faker->randomElements(['Desayuno incluido', 'Wi-Fi', 'Aire acondicionado'], 3),
            'status' => 'approved',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }
}
