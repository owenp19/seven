<?php

namespace App\Http\Resources\V1;

use App\Support\BookableType;
use App\Support\WhatsApp;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request): array
    {
        $bookableType = BookableType::label($this->bookable);

        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            // El token de acceso se entrega SOLO en la respuesta de creación
            // (BookingService fija el atributo transitorio plain_access_token
            // para esa única entrega). El resto de respuestas no lo incluyen
            // y el hash almacenado jamás se serializa.
            'booking_access_token' => $this->when(
                isset($this->plain_access_token) && is_string($this->plain_access_token),
                $this->plain_access_token,
            ),
            'type' => $this->type,
            'status' => $this->status,
            'bookable' => $this->whenLoaded('bookable', fn () => [
                'type' => $bookableType,
                'id' => $this->bookable->id,
                'name' => $this->bookable->name,
            ]),
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'guests' => $this->guests,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'special_requests' => $this->special_requests,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'created_at' => $this->created_at?->toIso8601String(),
            'whatsapp' => $this->whenLoaded('bookable', fn () => $this->whatsapp()),
        ];
    }

    /**
     * Deep-link wa.me hacia el dueño del servicio con el mensaje prellenado
     * (incluye el código de la solicitud). Contacto: whatsapp del bookable,
     * teléfono del bookable o teléfono del provider, en ese orden.
     */
    private function whatsapp(): ?array
    {
        $bookable = $this->bookable;

        if ($bookable === null) {
            return null;
        }

        $contact = $bookable->whatsapp ?? $bookable->phone ?? null;

        if (! is_string($contact) || $contact === '') {
            $contact = $this->relationLoaded('provider') ? ($this->provider?->phone ?? null) : null;
        }

        $message = $this->whatsappMessage($bookable->name ?? class_basename($bookable));

        $url = WhatsApp::link($contact, $message);

        if ($url === null) {
            return null;
        }

        return [
            'to' => $contact,
            'url' => $url,
            'message' => $message,
        ];
    }

    private function whatsappMessage(string $serviceName): string
    {
        $date = $this->start_at?->translatedFormat('d/m/Y H:i');

        return sprintf(
            'Hola, desde SEVEN San Andrés comparto mi solicitud %s para %s.%sPersonas: %d. ¿Me confirmas disponibilidad?',
            $this->booking_code,
            $serviceName,
            $date !== null && $date !== '' ? sprintf(' Fecha: %s.', $date) : '',
            $this->guests,
        );
    }
}
