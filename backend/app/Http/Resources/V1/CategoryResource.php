<?php

namespace App\Http\Resources\V1;

use App\Models\Category;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'icon' => $this->icon,
            'image' => mediaUrl($this->image),
            'description' => $this->description,
            // `sort_order` viaja porque el cliente lo lee; el modelo lo
            // mantiene como espejo de `order`. `status` es la única fuente
            // de verdad: antes caía a `is_active`, una rama que ya no
            // describía lo que el listado filtraba.
            'sort_order' => $this->sort_order,
            'order' => $this->order,
            'status' => $this->status ?? Category::ACTIVE,
            'business_count' => $this->whenCounted('businesses'),
        ];
    }
}
