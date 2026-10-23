<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

/**
 * PromotionPolicy — propiedad de una promoción en el panel del proveedor.
 *
 * Misma regla que ServicePolicy: solo el dueño del proveedor puede ver o
 * modificar su promoción. Los admins del backoffice no llegan aquí: los
 * resuelve Gate::before. Es defensa en profundidad: el scoping de
 * AdminCrudController ya hace que un recurso ajeno responda 404.
 */
class PromotionPolicy
{
    public function view(User $user, Promotion $promotion): bool
    {
        return $this->owns($user, $promotion);
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $this->owns($user, $promotion);
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $this->owns($user, $promotion);
    }

    private function owns(User $user, Promotion $promotion): bool
    {
        $provider = $user->provider;

        return $provider !== null
            && $promotion->provider_id !== null
            && (int) $promotion->provider_id === (int) $provider->id;
    }
}
