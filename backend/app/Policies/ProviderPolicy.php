<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\Role;
use App\Models\User;

/**
 * ProviderPolicy — perfil del proveedor en el panel /provider/providers.
 *
 * El dueño ve y edita su propia ficha (el scoping ya limita el listado a
 * esa fila); el borrado queda reservado a los admins globales y además lo
 * bloquea AdminCrudController mientras el proveedor tenga servicios.
 */
class ProviderPolicy
{
    public function view(User $user, Provider $provider): bool
    {
        return $this->owns($user, $provider);
    }

    public function update(User $user, Provider $provider): bool
    {
        return $this->owns($user, $provider);
    }

    public function delete(User $user, Provider $provider): bool
    {
        return $user->hasAnyRole(Role::ADMIN);
    }

    private function owns(User $user, Provider $provider): bool
    {
        return $user->provider !== null
            && (int) $user->provider->id === (int) $provider->id;
    }
}
