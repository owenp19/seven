<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class TourResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'category' => $this->category,
            'location' => $this->location,
            'duration' => $this->duration,
            'price' => (int) $this->price,
            'rating' => (float) $this->rating,
            'reviews' => (int) $this->reviews,
            'cover_image' => mediaUrl($this->cover_image),
            'status' => $this->status,
            'images' => $this->whenLoaded('images', fn () => $this->images->map(
                fn ($image) => [
                    'id' => $image->id,
                    'image' => mediaUrl($image->image),
                    'order' => $image->order,
                ]
            )),
            'includes' => $this->whenLoaded('includes', fn () => $this->includes->map(
                fn ($item) => ['icon' => $item->icon, 'name' => $item->name]
            )),
            'recommendations' => $this->whenLoaded('recommendations', fn () => $this->recommendations->map(
                fn ($item) => ['icon' => $item->icon, 'name' => $item->name]
            )),
            'provider' => $this->whenLoaded('provider', fn () => new ProviderResource($this->provider)),
        ];
    }
}
