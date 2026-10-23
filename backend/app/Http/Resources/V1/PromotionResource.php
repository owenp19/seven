<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'title' => $this->title,
            'description' => $this->description,
            'image' => mediaUrl($this->image),
            'discount' => (int) $this->discount,
            'price' => $this->price === null ? null : (int) $this->price,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'provider' => $this->whenLoaded('provider', fn () => new ProviderResource($this->provider)),
        ];
    }
}
