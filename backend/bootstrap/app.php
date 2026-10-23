<?php

use App\Exceptions\ApiExceptionHandler;
use App\Http\Middleware\AdminAuthMiddleware;
use App\Http\Middleware\AdminTokenMiddleware;
use App\Http\Middleware\ApiVersionMiddleware;
use App\Http\Middleware\EnsureSecureProductionConfig;
use App\Http\Middleware\EnsureTokenAbility;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ProviderAuthMiddleware;
use App\Http\Middleware\ScopeToProvider;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetHttpCacheHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'api.version' => ApiVersionMiddleware::class,
            'http.cache' => SetHttpCacheHeaders::class,
            // Legacy: token compartido directo, disponible por compatibilidad.
            'admin.token' => AdminTokenMiddleware::class,
            // Migración Sanctum: autenticación y autorización por rol.
            'admin.auth' => AdminAuthMiddleware::class,
            'role' => EnsureUserHasRole::class,
            // Ability de Sanctum (separación admin/provider) y permiso granular.
            'ability' => EnsureTokenAbility::class,
            'permission' => EnsureUserHasPermission::class,
            // Aislamiento multi-tenant del panel /provider/*.
            'provider.auth' => ProviderAuthMiddleware::class,
            'provider.scope' => ScopeToProvider::class,
        ]);

        // Fail-secure global de producción (APP_DEBUG / token legacy). Es
        // middleware HTTP: los comandos artisan no pasan por él.
        $middleware->append(EnsureSecureProductionConfig::class);

        $middleware->api(append: [SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiExceptionHandler::render($e, $request);
            }
        });
    })->create();
