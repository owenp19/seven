<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuario administrativo expuesto por /api/v1/admin/auth/*.
 *
 * No incluye `password`, `remember_token` ni la columna legacy `role`:
 * los roles canónicos salen de la pivote `role_user`.
 */
class AdminUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->rolesNames(),
            'permissions' => $this->permissions()->pluck('name')->values()->all(),
            'created_at' => $this->created_at,
        ];
    }
}
