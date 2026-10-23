<?php

$isProduction = env('APP_ENV') === 'production';

$configuredOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
)));

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | SEVEN is a hybrid app (Angular + Ionic) that runs on the web, Android
    | and iOS: in production the API origin (CORS_ALLOWED_ORIGINS) must be
    | declared explicitly. The permissive localhost/Capacitor patterns are
    | only applied outside production so a deployment can never accept a
    | localhost Origin over the internet.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $configuredOrigins !== []
        ? $configuredOrigins
        : ($isProduction ? [] : [
            'http://localhost:8100',
            'http://localhost:4200',
            'http://localhost:8101',
            'http://localhost:8300',
        ]),

    // Origins that change per dev port (ng serve 4200/8300, Ionic 8100,
    // Capacitor native webview). Patterns keep CORS working no matter which
    // local port the client is served from without editing this file.
    'allowed_origins_patterns' => $isProduction ? [] : [
        '/^https?:\/\/(localhost|127\.0\.0\.1)(:[0-9]+)?$/',
        '/^((capacitor|ionic):\/\/)(localhost|127\.0\.0\.1)(:[0-9]+)?$/',
    ],

    'allowed_headers' => ['*'],

    // SetHttpCacheHeaders emits an ETag on the cached catalog endpoints, but
    // a browser can only read a response header that is explicitly exposed.
    // Without this, cross-origin `If-None-Match` never matches and the
    // 304s the backend is already serving are wasted.
    'exposed_headers' => ['ETag'],

    'max_age' => 86400,

    'supports_credentials' => false,

];
