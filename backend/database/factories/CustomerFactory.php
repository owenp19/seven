<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone' => '+57'.$this->faker->numerify('3##########'),
            'email' => $this->faker->unique()->safeEmail(),
            'guest_token' => $this->faker->uuid(),
        ];
    }
}
