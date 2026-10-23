<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ExperienceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'duration_minutes' => $this->duration_minutes,
            'price_per_person' => $this->price_per_person,
            'currency' => $this->currency,
            'max_capacity' => $this->max_capacity,
            'address' => $this->address,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'includes' => $this->includes ?? [],
            'images' => mediaUrls($this->images),
            'rating_avg' => (float) ($this->rating_avg ?? 0),
            'rating_count' => (int) ($this->rating_count ?? 0),
            'status' => $this->status,
        ];
    }
}
