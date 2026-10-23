<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ChecksRoles;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureUserHasRole — autorización por rol.
 *
 * Uso en rutas: `->middleware('role:SUPER_ADMIN,ADMIN_SEVEN')`. Si no se
 * declaran roles, se exige cualquiera de los roles admin por defecto
 * (fail-closed: un usuario autenticado sin rol no entra).
 *
 * Debe ir después de `auth:sanctum` (o de `admin.auth`, que ya autenticó)
 * en la misma pila. El camino legacy del token compartido queda exento
 * durante la transición: se marcó en AdminAuthMiddleware y aquí se lee.
 *
 * Ejemplos:
 *   Route::middleware(['auth:sanctum', 'role:PROVEEDOR_TURISTICO'])
 *   Route::middleware(['admin.auth', 'role:SUPER_ADMIN,ADMIN_SEVEN'])
 */
class EnsureUserHasRole
{
    use ChecksRoles;

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if ($request->attributes->get(AdminAuthMiddleware::ATTR_AUTH_VIA) === AdminAuthMiddleware::VIA_LEGACY_TOKEN) {
            return $next($request);
        }

        $user = $request->user('sanctum') ?? $request->user();

        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }

        if (! $this->userSatisfiesRoles($user, $roles)) {
            abort(403, 'This action requires an authorized role.');
        }

        return $next($request);
    }
}
