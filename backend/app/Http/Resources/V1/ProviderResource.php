<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'business_name' => $this->business_name,
            'owner_name' => $this->owner_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'logo' => mediaUrl($this->logo),
            'type' => $this->type,
            'status' => $this->status,
            'verification_status' => $this->verification_status,
        ];
    }
}
