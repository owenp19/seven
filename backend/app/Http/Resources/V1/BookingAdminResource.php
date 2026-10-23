<?php

namespace App\Http\Resources\V1;

use App\Support\BookableType;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * BookingAdminResource
 *
 * Vista del dueño para una solicitud: datos completos del cliente y el
 * proveedor, estado con etiqueta en español y (cuando se cargan) las
 * transiciones de estado para la trazabilidad.
 */
class BookingAdminResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'type' => $this->type,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'guests' => $this->guests,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'special_requests' => $this->special_requests,
            'bookable' => $this->whenLoaded('bookable', fn () => [
                'type' => BookableType::label($this->bookable),
                'id' => $this->bookable->id,
                'name' => $this->bookable->name,
                'category_id' => $this->bookable->category_id ?? null,
                'provider_id' => $this->bookable->provider_id ?? null,
            ]),
            'provider' => $this->whenLoaded('provider', fn () => [
                'id' => $this->provider?->id,
                'business_name' => $this->provider?->business_name,
                'phone' => $this->provider?->phone,
            ]),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'email' => $this->customer->email,
            ]),
            'histories' => $this->whenLoaded('histories', fn () => $this->histories->map(
                fn ($history) => [
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'changed_by' => $history->changed_by,
                    'note' => $history->note,
                    'created_at' => $history->created_at?->toIso8601String(),
                ]
            )),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Nueva',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Cancelada',
            'completed' => 'Completada',
            default => (string) $this->status,
        };
    }
}
