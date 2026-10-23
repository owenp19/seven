<?php

namespace App\Repositories;

use App\Models\Booking;

class BookingRepository extends BaseRepository
{
    public function __construct(Booking $booking)
    {
        parent::__construct($booking);
    }

    public function findByCode(string $code): ?Booking
    {
        return $this->query()
            ->with(['bookable', 'customer', 'provider'])
            ->where('booking_code', $code)
            ->first();
    }

    /**
     * Reservas creadas por un dispositivo concreto.
     *
     * Es el ámbito correcto para "mis reservas" en una API sin login: el
     * install_id identifica el teléfono, no el teléfono escrito a mano.
     */
    public function forDevice(string $installId)
    {
        return $this->query()
            ->with(['bookable', 'customer', 'provider'])
            ->where('install_id', $installId)
            ->latest();
    }
}
