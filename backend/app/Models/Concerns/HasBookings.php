<?php

namespace App\Models\Concerns;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasBookings
 *
 * Shared by every model that can be reserved: Business,
 * Accommodation, Experience, Tour, Restaurant and Inn. Keeps the
 * polymorphic booking relation in a single place.
 */
trait HasBookings
{
    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }
}
