<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class BakeryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'logo' => mediaUrl($this->logo),
            'cover_image' => mediaUrl($this->cover_image),
            'description' => $this->description,
            'address' => $this->address,
            'phone' => $this->phone,
            'category' => $this->category,
            'rating' => (float) $this->rating,
            'reviews' => (int) $this->reviews,
            'opening_hours' => $this->opening_hours,
            'price_range' => $this->price_range,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'status' => $this->status,
            'products' => $this->whenLoaded('products', fn () => $this->products->map(
                fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => (int) $product->price,
                    'image' => mediaUrl($product->image),
                    'category' => $product->category,
                    'status' => $product->status,
                ]
            )),
            'provider' => $this->whenLoaded('provider', fn () => new ProviderResource($this->provider)),
        ];
    }
}
