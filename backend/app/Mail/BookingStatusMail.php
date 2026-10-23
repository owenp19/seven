<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingStatusMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
        public readonly ?string $previousStatus = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "SEVEN — {$this->title()}".($this->booking->booking_code ? " · {$this->booking->booking_code}" : ''),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.booking-status');
    }

    public function title(): string
    {
        return match ($this->booking->status) {
            'confirmed' => '¡Tu reserva ha sido confirmada',
            'cancelled' => 'Tu reserva fue cancelada',
            'completed' => 'Tu reserva fue completada',
            default => 'Tu solicitud de reserva fue recibida',
        };
    }
}
