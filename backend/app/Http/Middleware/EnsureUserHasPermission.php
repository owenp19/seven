<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureUserHasPermission — autorización granular por permiso.
 *
 * Uso en rutas: `->middleware('permission:providers.manage')`. Exige que el
 * usuario autenticado haya heredado al menos uno de los permisos indicados
 * a través de sus roles (role_user -> permission_role).
 *
 * SUPER_ADMIN conserva un bypass global; el resto (ADMIN_SEVEN incluido)
 * debe tener el permiso explícito. El camino legacy —solo local/testing—
 * queda exento porque no representa una cuenta individual.
 */
class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if ($request->attributes->get(AdminAuthMiddleware::ATTR_AUTH_VIA) === AdminAuthMiddleware::VIA_LEGACY_TOKEN) {
            return $next($request);
        }

        $user = $request->user('sanctum') ?? $request->user();

        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }

        if ($user->hasRole(Role::SUPER_ADMIN)) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'This action requires an authorized permission.');
    }
}
