<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'file_path' => mediaUrl($this->file_path),
            'type' => $this->type,
            'order' => (int) $this->order,
            // El panel necesita saber a qué entidad está anclado el archivo
            // para poder reasignarlo o detectar huérfanos.
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
        ];
    }
}
