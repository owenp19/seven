<?php

namespace App\Services;

use App\Events\BookingStatusChanged;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use Illuminate\Support\Facades\DB;

/**
 * BookingStatusService
 *
 * Punto único de cambio de estado de una solicitud: valida la transición
 * contra la máquina de estados de Booking::TRANSITIONS, persiste el nuevo
 * estado, registra la transición en booking_status_histories (trazabilidad)
 * y dispara BookingStatusChanged en cola para notificaciones. Ningún
 * controlador debe tocar status directamente.
 */
class BookingStatusService
{
    /** Estado de partida confirmado bajo bloqueo, para el evento. */
    private string $lockedFrom = '';

    /**
     * @param  'admin'|'customer'|'system'|'provider'  $changedBy
     */
    public function transition(Booking $booking, string $to, string $changedBy = 'system', ?string $note = null): Booking
    {
        abort_unless(
            in_array($to, Booking::STATUSES, true),
            422,
            "Invalid booking status [{$to}].",
        );

        $from = $booking->status;

        if ($from === $to) {
            return $booking;
        }

        $this->lockedFrom = '';

        DB::transaction(function () use ($booking, $to, $changedBy, $note) {
            // Re-leemos la fila con FOR UPDATE: dos peticiones concurrentes
            // sobre la misma reserva deben serializarse aquí, de modo que la
            // matriz se evalúe contra el estado realmente persistido y no
            // contra una copia obsoleta del modelo en memoria.
            $locked = Booking::whereKey($booking->getKey())->lockForUpdate()->firstOrFail();

            $from = $locked->status;

            if ($from === $to) {
                return;
            }

            $this->guardTransition($from, $to);

            $locked->forceFill(['status' => $to])->save();

            BookingStatusHistory::create([
                'booking_id' => $locked->id,
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $changedBy,
                'note' => $note,
            ]);

            $booking->setRawAttributes($locked->getAttributes(), true);
            $this->lockedFrom = $from;
        });

        if ($this->lockedFrom !== '') {
            event(new BookingStatusChanged($booking, $this->lockedFrom));
        }

        return $booking;
    }

    /**
     * Valida una arista contra Booking::TRANSITIONS.
     *
     * 409 y no 422: el estado enviado es un valor válido del enum, lo que
     * falla es la combinación actual -> destino (p. ej. resucitar una
     * reserva cancelada).
     */
    private function guardTransition(string $from, string $to): void
    {
        $allowed = Booking::TRANSITIONS[$from] ?? [];

        if (in_array($to, $allowed, true)) {
            return;
        }

        abort(409, $allowed === []
            ? "A booking in status [{$from}] is final and cannot change."
            : "Cannot change booking status from [{$from}] to [{$to}]. Allowed: ".implode(', ', $allowed).'.');
    }

    /** Registra la creación de una solicitud (from_status = null). */
    public function recordCreation(Booking $booking): void
    {
        BookingStatusHistory::create([
            'booking_id' => $booking->id,
            'from_status' => null,
            'to_status' => $booking->status,
            'changed_by' => 'customer',
        ]);
    }
}
