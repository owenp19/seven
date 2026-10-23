<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'booking_code' => strtoupper('SEV-'.$this->faker->bothify('####??')),
            'bookable_type' => Business::class,
            'bookable_id' => Business::factory(),
            'type' => $this->faker->randomElement(Booking::TYPES),
            'start_at' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'end_at' => null,
            'guests' => $this->faker->numberBetween(1, 8),
            'total_amount' => $this->faker->numberBetween(50000, 1200000),
            'currency' => 'COP',
            'special_requests' => $this->faker->optional()->sentence(),
            // Determinista a propósito: BookingService siempre crea en
            // 'pending' y `completed` es terminal, así que un estado
            // aleatorio hacía fallar pruebas de transición según el azar.
            'status' => 'pending',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed']);
    }
}
