<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminAuthMiddleware — autenticación de /api/v1/admin/*.
 *
 * Acepta dos caminos, en este orden:
 *
 *  1. Token compartido legacy (transición): cabecera X-Admin-Token o
 *     `Bearer` igual a ADMIN_API_TOKEN, SOLO mientras
 *     `api.admin.legacy_enabled` (ADMIN_LEGACY_TOKEN_ENABLED) esté activo Y
 *     el entorno sea local/testing. En producción el camino legacy queda
 *     cerrado aunque la variable esté activada: un token compartido no es
 *     auditable ni revocable por usuario, así que no puede ser la puerta de
 *     producción. `legacy_enabled` es false por defecto.
 *
 *  2. Sanctum (destino): `Authorization: Bearer <personal access token>`.
 *     Resuelve el usuario real; si el token es inválido responde 401.
 *
 * La autorización (rol, ability, permiso) no es responsabilidad de este
 * middleware: la hacen EnsureUserHasRole / EnsureTokenAbility /
 * EnsureUserHasPermission en la misma pila de rutas. El camino legacy se
 * marca en los atributos de la petición y esos middlewares lo eximen, pero
 * solo puede darse en local/testing por la guarda de entorno de arriba.
 *
 * Completa la migración a usuarios individuales con
 * ADMIN_LEGACY_TOKEN_ENABLED=false (el camino 1 desaparece y solo queda
 * Sanctum).
 */
class AdminAuthMiddleware
{
    /** Atributo de petición que indica cómo se autenticó la llamada. */
    public const ATTR_AUTH_VIA = 'seven.auth_via';

    public const VIA_SANCTUM = 'sanctum';

    public const VIA_LEGACY_TOKEN = 'legacy_token';

    public function handle(Request $request, Closure $next): Response
    {
        $legacy = (string) config('api.admin.token', '');
        $legacyEnabled = (bool) config('api.admin.legacy_enabled', false);

        // El token compartido solo se acepta fuera de producción. En prod,
        // EnsureSecureProductionConfig ya aborta la petición si la variable
        // está activada; esta guarda cierra además el camino en runtime.
        $legacyAllowed = $legacyEnabled
            && in_array((string) config('app.env'), ['local', 'testing'], true);

        // 1) Camino legacy (solo durante la transición y fuera de producción).
        if ($legacyAllowed && $legacy !== '') {
            $provided = $request->header('X-Admin-Token') ?: $this->bearerToken($request);

            if (is_string($provided) && $provided !== '' && hash_equals($legacy, $provided)) {
                $request->attributes->set(self::ATTR_AUTH_VIA, self::VIA_LEGACY_TOKEN);

                return $next($request);
            }
        }

        // 2) Camino Sanctum: el token debe resolver un usuario.
        if ($this->bearerToken($request) !== null) {
            if (Auth::guard('sanctum')->user() === null) {
                throw new AuthenticationException('Unauthenticated.');
            }

            $request->attributes->set(self::ATTR_AUTH_VIA, self::VIA_SANCTUM);

            return $next($request);
        }

        // Sin cabecera Authorization: fail-closed original durante la
        // transición (solo local/testing). Con el legacy desactivado —el
        // caso de producción— no se autentica: 401.
        if ($legacyAllowed) {
            if ($legacy === '') {
                abort(503, 'Admin endpoints are not enabled (ADMIN_API_TOKEN not set).');
            }

            abort(401, 'Invalid admin token.');
        }

        throw new AuthenticationException('Unauthenticated.');
    }

    private function bearerToken(Request $request): ?string
    {
        $header = (string) $request->header('Authorization', '');

        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
