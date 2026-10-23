<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ScopeToProvider — aísla el panel /provider/* al proveedor del usuario.
 *
 * Uso en rutas: `->middleware(['admin.auth', 'role:PROVEEDOR_TURISTICO',
 * 'provider.scope'])`. Resuelve el proveedor dueño de la cuenta (relación
 * User::provider) y lo deja en los atributos del request; de ahí lo leen
 * tenantProvider() (helpers) y AdminCrudController para filtrar cada query
 * por provider_id e inyectar el tenant en altas y ediciones.
 *
 * Fail-closed: sin usuario Sanctum, sin proveedor vinculado o con el token
 * compartido legacy (que no pertenece a ninguna cuenta) la petición se
 * rechaza con 403.
 */
class ScopeToProvider
{
    public const ATTR_PROVIDER = 'seven.provider';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get(AdminAuthMiddleware::ATTR_AUTH_VIA) === AdminAuthMiddleware::VIA_LEGACY_TOKEN) {
            abort(403, 'The provider panel requires a user account.');
        }

        $user = $request->user('sanctum') ?? $request->user();

        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $provider = $user->provider;

        if ($provider === null) {
            abort(403, 'No provider is linked to this account.');
        }

        $request->attributes->set(self::ATTR_PROVIDER, $provider);

        return $next($request);
    }
}
