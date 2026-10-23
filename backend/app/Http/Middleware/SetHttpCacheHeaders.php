<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetHttpCacheHeaders
 *
 * Aplica cabeceras de caché pública + ETag a respuestas GET del catálogo
 * (casi inmutable), de modo que clientes/proxies/CDN pueden revalidar con
 * If-None-Match y recibir 304 sin re-generar el body completo. El trabajo
 * pesado ya lo evita CatalogCache al nivel de la aplicación.
 *
 * Uso: ->middleware('http.cache')  (o 'http.cache:60' para TTL en segundos)
 */
class SetHttpCacheHeaders
{
    public function handle(Request $request, Closure $next, ?string $seconds = null): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return $response;
        }

        $seconds = $seconds !== null
            ? (int) $seconds
            : (int) config('api.catalog.http_cache_seconds', 300);

        if ($seconds > 0) {
            $response->headers->set('Cache-Control', "public, max-age={$seconds}, s-maxage={$seconds}");
        }

        $etag = '"'.md5((string) $response->getContent()).'"';
        $response->headers->set('ETag', $etag);

        if ($request->headers->get('If-None-Match') === $etag) {
            return new Response('', 304, $response->headers->all());
        }

        return $response;
    }
}
