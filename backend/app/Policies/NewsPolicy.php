<?php

namespace App\Policies;

use App\Models\News;
use App\Models\User;

/**
 * NewsPolicy — propiedad de una noticia en el panel del proveedor.
 *
 * Misma regla que ServicePolicy: solo el dueño del proveedor puede ver o
 * modificar su noticia. Los admins del backoffice no llegan aquí: los
 * resuelve Gate::before. Es defensa en profundidad: el scoping de
 * AdminCrudController ya hace que un recurso ajeno responda 404.
 */
class NewsPolicy
{
    public function view(User $user, News $news): bool
    {
        return $this->owns($user, $news);
    }

    public function update(User $user, News $news): bool
    {
        return $this->owns($user, $news);
    }

    public function delete(User $user, News $news): bool
    {
        return $this->owns($user, $news);
    }

    private function owns(User $user, News $news): bool
    {
        $provider = $user->provider;

        return $provider !== null
            && $news->provider_id !== null
            && (int) $news->provider_id === (int) $provider->id;
    }
}
