<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'image' => mediaUrl($this->image),
            'kind' => $this->kind,
            'link_url' => $this->link_url,
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'provider' => $this->whenLoaded('provider', fn () => new ProviderResource($this->provider)),
        ];
    }
}
