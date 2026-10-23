<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * ApiVersionMiddleware
 *
 * Normalizes an explicit API version from the request path into a
 * request attribute (`api.version`) and validates it against the list
 * of supported versions registered in `config('api.versions')`.
 *
 * Example:
 *   GET /api/v1/businesses  ->  $request->get('api.version') === 'v1'
 */
class ApiVersionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('api.versions', ['v1']);
        $version = strtolower($request->segment(2) ?? '');

        if (! in_array($version, $supported, true)) {
            throw new NotFoundHttpException("API version '{$version}' is not supported.");
        }

        $request->attributes->set('api.version', $version);

        return $next($request);
    }
}
