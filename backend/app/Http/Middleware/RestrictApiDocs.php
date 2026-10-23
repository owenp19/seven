<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * RestrictApiDocs
 *
 * La documentación OpenAPI/Swagger describe toda la superficie de la API
 * (incluidas las rutas /admin y sus esquemas) y se sirve sin autenticación.
 * En producción queda deshabilitada y responde 404 —el mismo código que una
 * ruta inexistente— para no revelar siquiera que existe. Fuera de producción
 * (o con API_DOCS_ENABLED=true) se mantiene disponible para el equipo.
 */
class RestrictApiDocs
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isProduction() && ! config('api.docs.enabled', false)) {
            throw new NotFoundHttpException;
        }

        return $next($request);
    }

    private function isProduction(): bool
    {
        return config('app.env') === 'production';
    }
}
