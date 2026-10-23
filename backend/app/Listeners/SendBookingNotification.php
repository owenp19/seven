<?php

namespace App\Listeners;

use App\Events\BookingStatusChanged;
use App\Mail\BookingStatusMail;
use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * SendBookingNotification
 *
 * Consumidor en cola de BookingStatusChanged:
 *  - Email al cliente cuando la reserva tiene un correo registrado.
 *  - Registro App\Models\Notification (tabla notifications, provider-scoped)
 *    cuando la reserva pertenece a un provider.
 *
 * Nunca se ejecuta en el request: el framework lo manda a la cola activa
 * (database en dev, redis en producción) al escuchar el evento.
 */
class SendBookingNotification implements ShouldQueue
{
    public function handle(BookingStatusChanged $event): void
    {
        $booking = $event->booking;

        $email = $booking->customer?->email;

        if (is_string($email) && $email !== '') {
            Mail::to($email)->send(new BookingStatusMail($booking, $event->previousStatus));
        }

        $providerId = $this->resolveProviderId($booking);

        if ($providerId !== null) {
            Notification::create([
                'provider_id' => $providerId,
                'title' => $this->titleFor($booking),
                'message' => $this->messageFor($booking),
                'type' => 'booking',
            ]);
        }
    }

    /** Provider dueño de la reserva: directo, o vía bookable (tours, inns...). */
    private function resolveProviderId(Booking $booking): ?int
    {
        if ($booking->provider_id !== null) {
            return (int) $booking->provider_id;
        }

        $bookable = $booking->bookable;

        if ($bookable !== null && isset($bookable->provider_id) && $bookable->provider_id !== null) {
            return (int) $bookable->provider_id;
        }

        return null;
    }

    private function titleFor(Booking $booking): string
    {
        return match ($booking->status) {
            'confirmed' => 'Nueva reserva confirmada',
            'cancelled' => 'Reserva cancelada',
            'completed' => 'Reserva completada',
            default => 'Nueva solicitud de reserva',
        };
    }

    private function messageFor(Booking $booking): string
    {
        $code = $booking->booking_code;

        return match ($booking->status) {
            'confirmed' => "La reserva {$code} ha sido confirmada: {$booking->guests} invitado(s), {$this->dates($booking)}.",
            'cancelled' => "La reserva {$code} ha sido cancelada.",
            'completed' => "La reserva {$code} fue completada.",
            default => "Recibiste una nueva solicitud de reserva ({$code}).",
        };
    }

    private function dates(Booking $booking): string
    {
        $start = $booking->start_at?->toDateString();

        if ($booking->end_at && $booking->end_at->toDateString() !== $start) {
            return "{$start} a {$booking->end_at->toDateString()}";
        }

        return (string) $start;
    }
}
