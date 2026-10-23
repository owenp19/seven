<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * ApiExceptionHandler
 *
 * Renders every exception thrown inside an `api/*` route using the
 * standard SEVEN JSON envelope, so the mobile/web clients always
 * receive a predictable payload instead of a framework stack page.
 */
class ApiExceptionHandler
{
    public static function render(Throwable $e, Request $request): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => apiResponse(
                success: false,
                message: 'The given data was invalid.',
                errors: $e->errors(),
                status: 422,
            ),

            $e instanceof ModelNotFoundException => apiResponse(
                success: false,
                message: 'Resource not found.',
                errors: ['resource' => ['The requested resource does not exist.']],
                status: 404,
            ),

            $e instanceof NotFoundHttpException => apiResponse(
                success: false,
                message: 'Route not found.',
                errors: ['route' => ['The requested endpoint does not exist.']],
                status: 404,
            ),

            $e instanceof AuthenticationException => apiResponse(
                success: false,
                message: 'Unauthenticated.',
                errors: ['auth' => ['Authentication is required to access this resource.']],
                status: 401,
            ),

            // Denegación de policy (p. ej. el dueño de un proveedor tocando
            // un recurso ajeno): siempre con envelope, nunca un 500.
            $e instanceof AuthorizationException => apiResponse(
                success: false,
                message: $e->getMessage() ?: 'This action is unauthorized.',
                status: 403,
            ),

            $e instanceof HttpException => apiResponse(
                success: false,
                message: $e->getMessage() ?: 'HTTP error.',
                status: $e->getStatusCode(),
            ),

            // El rate limiter (throttle) lanza la respuesta 429 ya construida
            // dentro de un HttpResponseException; si cae en renderUnexpected
            // se convierte en un 500 genérico y ningún limite llega al
            // cliente. Se devuelve la respuesta embebida tal cual.
            $e instanceof HttpResponseException => self::fromHttpResponse($e->getResponse()),

            default => self::renderUnexpected($e),
        };
    }

    /** Re-expone la respuesta JSON del throttle como envelope de SEVEN. */
    private static function fromHttpResponse(Response $response): JsonResponse
    {
        return $response instanceof JsonResponse
            ? $response
            : new JsonResponse($response->getContent(), $response->getStatusCode());
    }

    private static function renderUnexpected(Throwable $e): JsonResponse
    {
        // Los detalles técnicos NO viajan al cliente por defecto, aunque
        // APP_DEBUG esté activo: un stack trace expone rutas del servidor,
        // versiones y, con frecuencia, fragmentos de credenciales en el
        // payload de las queries. Solo se incluyen si se habilita
        // explícitamente con API_DEBUG_TRACE=true (entorno local).
        if (config('app.debug') && config('api.debug_trace', false)) {
            return apiResponse(
                success: false,
                message: $e->getMessage(),
                data: [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => array_slice($e->getTrace(), 0, 5),
                ],
                status: 500,
            );
        }

        return apiResponse(
            success: false,
            message: 'Server error.',
            errors: ['server' => ['An unexpected error occurred. Please try again later.']],
            status: 500,
        );
    }
}
