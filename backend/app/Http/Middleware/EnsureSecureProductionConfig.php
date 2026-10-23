<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureSecureProductionConfig — fail-secure de configuración.
 *
 * En producción, una configuración insegura no puede quedarse en un simple
 * Log::error: la petición HTTP se aborta con 503 hasta que se corrija. Se
 * vigilan los dos errores que exponen el sistema en prod:
 *
 *  - APP_DEBUG=true: stack traces, rutas y datos de queries al cliente.
 *  - ADMIN_LEGACY_TOKEN_ENABLED=true: token compartido, no auditable ni
 *    revocable por usuario.
 *
 * Diseño compatible con el despliegue: es un middleware GLOBAL de HTTP, así
 * que los comandos artisan (migrate, config:cache, optimize, queue:work...)
 * no pasan por él y nunca se ven bloqueados. El escape hatch
 * SECURITY_ENFORCE_PRODUCTION=false existe para tests.
 */
class EnsureSecureProductionConfig
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('api.security.enforce_production', true)) {
            return $next($request);
        }

        // Se compara contra config y no contra app()->environment(): así el
        // guard refleja el APP_ENV realmente cargado (y es testeable).
        if (config('app.env') !== 'production') {
            return $next($request);
        }

        $problems = [];

        if (config('app.debug')) {
            $problems[] = 'APP_DEBUG must be false in production.';
        }

        if (config('api.admin.legacy_enabled')) {
            $problems[] = 'ADMIN_LEGACY_TOKEN_ENABLED must be false in production.';
        }

        if ($problems === []) {
            return $next($request);
        }

        Log::critical('Insecure production configuration detected; HTTP requests are blocked.', [
            'problems' => $problems,
        ]);

        abort(503, 'Service unavailable: insecure production configuration.');
    }
}
