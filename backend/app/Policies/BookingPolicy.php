<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * BookingPolicy — reservas del panel del proveedor.
 *
 * Solo el dueño puede consultar o cambiar el estado de una reserva. Las
 * reservas heredadas sin provider_id se resuelven por el dueño del
 * bookable (misma regla que usa BookingService al crear la reserva).
 * Los admins globales los resuelve Gate::before.
 */
class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $this->owns($user, $booking);
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->owns($user, $booking);
    }

    private function owns(User $user, Booking $booking): bool
    {
        $provider = $user->provider;

        if ($provider === null) {
            return false;
        }

        if ($booking->provider_id !== null) {
            return (int) $booking->provider_id === (int) $provider->id;
        }

        $bookable = $booking->bookable;

        return $bookable !== null
            && $bookable->provider_id !== null
            && (int) $bookable->provider_id === (int) $provider->id;
    }
}
