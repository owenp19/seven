@component('mail::message')
# {{ $booking->status === 'confirmed' ? '¡Reserva confirmada!' : ($booking->status === 'cancelled' ? 'Reserva cancelada' : 'Estimado viajero') }}

**Código de reserva:** {{ $booking->booking_code }}

**Estado:** {{ ucfirst($booking->status) }}

**Inicio:** {{ $booking->start_at?->translatedFormat('l, d F Y \a \l\a\s H:i') }}

@if ($booking->end_at)
**Fin:** {{ $booking->end_at->translatedFormat('l, d F Y \a \l\a\s H:i') }}
@endif

**Invitados:** {{ $booking->guests }}

**Total:** {{ number_format((float) $booking->total_amount, 0, ',', '.') }} {{ $booking->currency }}

@if ($booking->special_requests)
**Peticiones especiales:** {{ $booking->special_requests }}
@endif

@if ($booking->status === 'pending')
Tu solicitud de reserva fue recibida y está pendiente de confirmación.
@elseif ($booking->status === 'cancelled')
Tu reserva ha sido cancelada. Si no la realizaste tú, contáctanos.
@elseif ($booking->status === 'completed')
Gracias por viajar con nosotros.
@else
¡Gracias por confiar en SEVEN! Nos vemos pronto.
@endif

@component('mail::button', ['url' => url('/')])
Visitar SEVEN
@endcomponent

Saludos,<br>
El equipo de **SEVEN**
@endcomponent