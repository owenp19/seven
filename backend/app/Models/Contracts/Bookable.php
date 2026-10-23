<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Bookable
 *
 * Everything that can be reserved through the polymorphic booking
 * relation. Implemented by every reservable vertical (Business,
 * Accommodation, Experience, Tour, Restaurant, Inn) so services can work
 * against a single contract instead of concrete model classes.
 */
interface Bookable
{
    public function bookings(): MorphMany;

    public function scopeApproved($query);
}
