<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Central configuration for the SEVEN REST API:
    | prefix, supported versions and default pagination size.
    |
    */

    'prefix' => env('API_PREFIX', 'v1'),

    'versions' => ['v1'],

    /*
    |--------------------------------------------------------------------------
    | Detalle de excepciones en la respuesta
    |--------------------------------------------------------------------------
    | Desactivado por defecto incluso con APP_DEBUG=true: un stack trace en
    | el body filtra rutas, versiones y a veces datos de queries. Actívalo
    | solo en local con API_DEBUG_TRACE=true.
    */
    'debug_trace' => env('API_DEBUG_TRACE', false),

    /*
    |--------------------------------------------------------------------------
    | Documentación OpenAPI / Swagger
    |--------------------------------------------------------------------------
    | Deshabilitada en producción por defecto (la ruta responde 404). Para
    | exponerla temporalmente en un entorno productivo, API_DOCS_ENABLED=true.
    */
    'docs' => [
        'enabled' => env('API_DOCS_ENABLED', false),
    ],

    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (por tier)
    |--------------------------------------------------------------------------
    | Respaldado por Redis en producción (CACHE_STORE=redis) para que el
    | contador sea compartido entre nodos y no golpee la base de datos.
    */
    'rate_limit' => [
        'guest' => (int) env('API_RATE_LIMIT_GUEST', 120),
        'authenticated' => (int) env('API_RATE_LIMIT_AUTHENTICATED', 600),
        'admin' => (int) env('API_RATE_LIMIT_ADMIN', 60),
        /*
         * Comentarios: control combinado anti-abuso.
         *
         * `per_device` (por hora) recuerda la regla de negocio "una opinión
         * por destino" como tope (el índice único de `comments` la hace
         * cumplir de verdad). `per_ip` (por hora) es generoso porque detrás
         * de NAT varios viajeros comparten IP, pero evita que rotar el
         * `X-Install-Id` permita fabricar reseñas sin cota. `per_minute`
         * corta ráfagas de peticiones inválidas. La IP nunca se persiste ni
         * identifica a una persona.
         */
        'comments' => [
            'per_device' => (int) env('API_RATE_LIMIT_COMMENTS', 5),
            'per_ip' => (int) env('API_RATE_LIMIT_COMMENTS_PER_IP', 30),
            'per_minute' => (int) env('API_RATE_LIMIT_COMMENTS_PER_MINUTE', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Panel de administración (backoffice)
    |--------------------------------------------------------------------------
    | Autenticación individual con Laravel Sanctum: el login de
    | /api/v1/admin/auth/login emite un token personal por usuario y las
    | rutas /api/v1/admin/* exigen ability `admin` + un rol autorizado
    | (role:SUPER_ADMIN,ADMIN_SEVEN) + el permiso concreto de cada grupo.
    |
    | `token` es el token compartido LEGACY (cabecera X-Admin-Token o
    | Bearer). Está DESHABILITADO por defecto (`legacy_enabled=false`) y,
    | aunque se active, AdminAuthMiddleware solo lo acepta en local/testing.
    | En producción queda cerrado incluso con la variable activada.
    |
    | `abilities` documenta las habilidades de Sanctum usadas por cada
    | panel. No sustituyen roles/permisos: son una primera barrera que
    | evita que un token de otra superficie acceda al panel equivocado.
    |
    | `login_roles` es el filtro de quién puede iniciar sesión: un usuario
    | sin ninguno de estos roles recibe 403 aunque acierte la contraseña.
    */
    'admin' => [
        'token' => env('ADMIN_API_TOKEN', ''),
        'legacy_enabled' => env('ADMIN_LEGACY_TOKEN_ENABLED', false),
        'login_roles' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('ADMIN_LOGIN_ROLES', 'SUPER_ADMIN,ADMIN_SEVEN')),
        ))),
        'abilities' => [
            'admin' => 'admin',
            'provider' => 'provider',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seguridad de configuración (fail-secure)
    |--------------------------------------------------------------------------
    | En producción, EnsureSecureProductionConfig aborta la petición HTTP
    | (503) si detecta configuración insegura: APP_DEBUG=true o el token
    | compartido legacy. Los comandos CLI (migrate, optimize...) no pasan
    | por middleware y por tanto nunca se ven afectados por este guard.
    |
    | SECURITY_ENFORCE_PRODUCTION=false solo debería usarse en tests.
    */
    'security' => [
        'enforce_production' => env('SECURITY_ENFORCE_PRODUCTION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media upload
    |--------------------------------------------------------------------------
    | Disco de almacenamiento ("public" en dev con storage:link, "s3" en
    | producción), tamaño máximo por archivo y tipos permitidos.
    |
    | `mimes` NO incluye svg. Un SVG subido se sirve desde el mismo origen
    | que la app, así que un <script> o un onload= dentro de él se ejecuta
    | con el privileges de la sesión: es XSS almacenado. Tampoco hay
    | re-encoding ni cap de dimensiones que lo neutralice. Los logos que
    | necesiten ser vectoriales deben subirse ya rasterizados.
    */
    'media' => [
        'disk' => env('MEDIA_DISK', 'public'),
        'max_kb' => (int) env('MEDIA_MAX_KB', 5120),
        'types' => ['image', 'logo', 'cover', 'gallery', 'product', 'banner', 'promotion', 'video'],
        'mimes' => 'jpeg,png,jpg,webp,gif,avif,mp4,webm',
    ],

    /*
    |--------------------------------------------------------------------------
    | Catálogo (casi inmutable)
    |--------------------------------------------------------------------------
    | Las listas de categorías, banners y promociones se cachean TTL
    | segundos y se invalidan al guardar/borrar su modelo. Las respuestas
    | llevan Cache-Control + ETag para multiplicar throughput con proxies.
    */
    'catalog' => [
        'ttl' => (int) env('API_CATALOG_TTL', 3600),
        'http_cache_seconds' => (int) env('API_CATALOG_HTTP_CACHE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp (flujo de solicitud)
    |--------------------------------------------------------------------------
    | Código de país que se antepone a los teléfonos locales al construir el
    | deep-link wa.me con el mensaje de confirmación de una solicitud.
    */
    'whatsapp' => [
        'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY', '57'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    | "like" → LIKE %q% (suficiente hoy). "fulltext" → MATCH...AGAINST
    | (requiere índice FULLTEXT en businesses(name, description)).
    */
    'search' => [
        'mode' => env('API_SEARCH_MODE', 'like'),
    ],

];
