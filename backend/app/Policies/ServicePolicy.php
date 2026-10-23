<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * ServicePolicy — propiedad de un servicio del marketplace.
 *
 * Registrada en AppServiceProvider contra los siete modelos de servicio
 * (Provider::serviceModels), de modo que tours, restaurants, inns, bakeries,
 * businesses, accommodations y experiences comparten esta regla: solo el
 * dueño del proveedor puede ver o modificar su servicio. Los admins
 * globales no llegan aquí: los resuelve Gate::before.
 *
 * Es defensa en profundidad: el scoping de AdminCrudController ya hace que
 * un recurso ajeno responda 404 en las queries.
 */
class ServicePolicy
{
    public function view(User $user, Model $service): bool
    {
        return $this->owns($user, $service);
    }

    public function update(User $user, Model $service): bool
    {
        return $this->owns($user, $service);
    }

    public function delete(User $user, Model $service): bool
    {
        return $this->owns($user, $service);
    }

    private function owns(User $user, Model $service): bool
    {
        $provider = $user->provider;

        return $provider !== null
            && $service->provider_id !== null
            && (int) $service->provider_id === (int) $provider->id;
    }
}
