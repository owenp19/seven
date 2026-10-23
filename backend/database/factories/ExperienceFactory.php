<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    protected $model = Experience::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Tour en lancha por manglares',
            'Clase de cocina raizal',
            'Caminata guiada al faro',
            'La vuelta completa a la isla',
            'Atardecer en kayak',
            'Ruta de café de la isla',
            'Buceo en el arrecife',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'description' => $this->faker->paragraph(3),
            'category_id' => null,
            'duration_minutes' => $this->faker->randomElement([60, 90, 120, 180, 240]),
            'price_per_person' => $this->faker->numberBetween(60000, 320000),
            'currency' => 'COP',
            'max_capacity' => $this->faker->numberBetween(2, 12),
            'address' => $this->faker->streetAddress(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'includes' => $this->faker->randomElements(['Guía local', 'Snorkel', 'Transporte', 'Snack'], 3),
            'images' => [$this->faker->imageUrl(800, 600, 'tour', true)],
            'status' => 'approved',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }
}
