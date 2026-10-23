<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permiso granular de SEVEN (`catalog.manage`, `bookings.manage`, ...).
 *
 * Los permisos no se asignan directamente al usuario: se heredan del rol
 * que tiene (relación `role_user` -> `permission_role`). La autorización
 * del backoffice es por rol; los permisos quedan disponibles para las
 * rutas futuras que necesiten un corte más fino.
 */
class Permission extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
