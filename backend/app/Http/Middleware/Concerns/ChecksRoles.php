<?php

namespace App\Http\Middleware\Concerns;

use App\Models\Role;
use App\Models\User;

/**
 * Comprobación de roles compartida entre los middlewares de autorización.
 *
 * Las comparaciones son case-insensitive para que `role:admin_seven` y
 * `ADMIN_SEVEN` resuelvan al mismo rol, pero la fuente de verdad sigue
 * siendo el nombre canónico guardado en `roles.name`.
 */
trait ChecksRoles
{
    /**
     * @param  array<int, string>  $allowed  roles aceptados; vacío = roles admin
     */
    protected function allowedRoles(array $allowed): array
    {
        return $allowed !== [] ? array_values($allowed) : Role::ADMIN;
    }

    protected function userSatisfiesRoles(?User $user, array $allowed): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole($this->allowedRoles($allowed));
    }
}
