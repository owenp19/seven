<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureTokenAbility — primera barrera de autorización por habilidad Sanctum.
 *
 * Uso en rutas: `->middleware('ability:admin')`. Exige que el token personal
 * que autenticó la petición tenga al menos una de las abilities indicadas
 * (o `*`). Así un token emitido para el panel del proveedor (`provider`) no
 * puede operar el backoffice ni al revés, aunque el usuario tuviera ambos
 * roles.
 *
 * NO sustituye a roles ni a policies: es solo la separación de superficies.
 * El camino legacy —solo posible en local/testing— queda exento porque no
 * tiene token personal asociado.
 */
class EnsureTokenAbility
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        if ($request->attributes->get(AdminAuthMiddleware::ATTR_AUTH_VIA) === AdminAuthMiddleware::VIA_LEGACY_TOKEN) {
            return $next($request);
        }

        $user = $request->user('sanctum') ?? $request->user();

        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $token = $user->currentAccessToken();

        if ($token === null) {
            abort(403, 'This resource requires a personal access token.');
        }

        foreach ($abilities as $ability) {
            if ($token->can($ability) || $token->can('*')) {
                return $next($request);
            }
        }

        abort(403, 'This token is not allowed to access this resource.');
    }
}
