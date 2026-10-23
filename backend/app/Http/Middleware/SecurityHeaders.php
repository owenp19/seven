<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeaders
 *
 * Cabeceras de endurecimiento para toda la API. CORS y CSP se resuelven por
 * entorno: en producción no se emiten orígenes locales y en local se evitaba
 * que la CSP rompiera el hot-reload de Vite, así que el CSP estricto solo
 * aplica fuera de localhost.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! $this->isLocalhost($request)) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'",
            );
        }

        return $response;
    }

    private function isLocalhost(Request $request): bool
    {
        $host = $request->getHost();

        return in_array($host, ['localhost', '127.0.0.1'], true)
            || str_starts_with($host, 'localhost');
    }
}
