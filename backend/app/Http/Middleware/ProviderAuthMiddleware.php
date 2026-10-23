<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ProviderAuthMiddleware — autenticación específica del panel /api/v1/provider/*
 *
 * Se coloca en la pila DESPUÉS de `admin.auth` (que resuelve la vía de
 * autenticación para ambos caminos) y endurece las dos cosas que el panel
 * del proveedor necesita además de una sesión válida:
 *
 *  - El token compartido legacy queda fuera: no pertenece a ninguna cuenta
 *    y por tanto no puede actuar como un proveedor (403).
 *  - El acceso exige un token personal de Sanctum con ABILIDAD `provider`.
 *    Un token emitido por /admin/auth/login (abilidad `admin`) no entra
 *    aquí aunque el usuario tuviera además el rol PROVEEDOR_TURISTICO.
 *
 * La autorización por rol (role:PROVEEDOR_TURISTICO) y el aislamiento del
 * tenant (provider.scope) corren en los middlewares siguientes de la pila.
 */
class ProviderAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get(AdminAuthMiddleware::ATTR_AUTH_VIA) === AdminAuthMiddleware::VIA_LEGACY_TOKEN) {
            abort(403, 'The provider panel requires a user account.');
        }

        $user = $request->user('sanctum') ?? $request->user();

        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $token = $user->currentAccessToken();

        if ($token === null || ! $token->can('provider')) {
            abort(403, 'The provider panel requires a provider token.');
        }

        return $next($request);
    }
}
