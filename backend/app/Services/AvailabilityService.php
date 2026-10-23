<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\Business;
use App\Models\Experience;
use App\Repositories\BookingRepository;
use Illuminate\Support\Carbon;

/**
 * AvailabilityService
 *
 * Provides a lightweight availability check used during the booking
 * flow. v1 exposes published business hours and whether a given date is
 * fully booked; in later versions this hooks into dynamic calendars.
 */
class AvailabilityService
{
    public function __construct(private readonly BookingRepository $bookings) {}

    public function forBusiness(Business $business, string $date): array
    {
        $day = strtolower(date('l', strtotime($date))); // monday, tuesday...
        $hours = data_get($business->opening_hours, $day);

        return [
            'business_id' => $business->id,
            'date' => $date,
            'is_open' => $hours !== null,
            'opening_hours' => $hours ?? null,
            'slots_taken' => $this->countBooked($business, $date),
        ];
    }

    public function forAccommodation(Accommodation $accommodation, string $date): array
    {
        return [
            'accommodation_id' => $accommodation->id,
            'date' => $date,
            'is_available' => $this->countBooked($accommodation, $date) === 0,
            'capacity' => $accommodation->capacity,
            'price_per_night' => $accommodation->price_per_night,
        ];
    }

    public function forExperience(Experience $experience, string $date): array
    {
        return [
            'experience_id' => $experience->id,
            'date' => $date,
            'is_available' => $this->countBooked($experience, $date) === 0,
            'max_capacity' => $experience->max_capacity,
            'price_per_person' => $experience->price_per_person,
        ];
    }

    private function countBooked(Business|Accommodation|Experience $model, string $date): int
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->copy()->endOfDay();

        $query = $this->bookings->query()
            ->where('bookable_type', $model::class)
            ->where('bookable_id', $model->id)
            ->where('status', '!=', 'cancelled');

        if ($model instanceof Accommodation) {
            // Una noche está ocupada si cualquier estancia la solapa:
            // entrada en días anteriores o incursión dentro del día.
            $query->where(function ($query) use ($start, $end) {
                $query->whereNull('end_at')->whereBetween('start_at', [$start, $end])
                    ->orWhere(fn ($query) => $query
                        ->whereNotNull('end_at')
                        ->where('start_at', '<', $end)
                        ->where('end_at', '>', $start));
            });
        } else {
            // Eventos / negocios: el horario empieza dentro del día consultado.
            $query->whereBetween('start_at', [$start, $end]);
        }

        return $query->count();
    }
}
