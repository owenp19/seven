<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * BookingStatusChanged
 *
 * Se lanza cada vez que una reserva cambia de estado (creada, confirmada,
 * cancelada, completada). El listener lo consume en cola para enviar el
 * email al cliente y crear la notificación en BD para el provider sin
 * bloquear el request que generó el cambio.
 */
class BookingStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Booking $booking,
        public readonly ?string $previousStatus = null,
    ) {}
}
