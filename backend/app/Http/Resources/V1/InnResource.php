<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class InnResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'cover_image' => mediaUrl($this->cover_image),
            'location' => $this->location,
            'price' => (int) $this->price,
            'services' => $this->services ?? [],
            'availability' => (bool) $this->availability,
            'rating' => (float) $this->rating,
            'status' => $this->status,
            'rooms' => $this->whenLoaded('rooms', fn () => $this->rooms->map(
                fn ($room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'price' => (int) $room->price,
                    'image' => mediaUrl($room->image),
                    'capacity' => (int) $room->capacity,
                ]
            )),
            'provider' => $this->whenLoaded('provider', fn () => new ProviderResource($this->provider)),
        ];
    }
}
