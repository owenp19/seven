<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminTokenMiddleware (LEGACY / deprecado)
 *
 * Protegía los endpoints de backoffice con un token compartido. Se
 * conserva registrado como `admin.token` por compatibilidad, pero:
 *
 *  - En producción queda cerrado: un token compartido no es auditable ni
 *    revocable por usuario. Usa Sanctum (/admin/auth/login).
 *  - Solo funciona en local/testing, igual que el camino legacy de
 *    AdminAuthMiddleware.
 *
 * No añadas rutas nuevas con este middleware; migra a `admin.auth`.
 */
class AdminTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array((string) config('app.env'), ['local', 'testing'], true)) {
            abort(503, 'The shared admin token is disabled outside local/testing. Use Sanctum.');
        }

        $token = config('api.admin.token');

        if ($token === null || $token === '') {
            abort(503, 'Admin endpoints are not enabled (ADMIN_API_TOKEN not set).');
        }

        $provided = $this->resolveToken($request);

        if ($provided === null || ! hash_equals($token, $provided)) {
            abort(401, 'Invalid admin token.');
        }

        return $next($request);
    }

    private function resolveToken(Request $request): ?string
    {
        $header = $request->header('X-Admin-Token');

        if (is_string($header) && $header !== '') {
            return $header;
        }

        $authorization = $request->header('Authorization');

        if (is_string($authorization)
            && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }
}
