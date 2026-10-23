<?php

use App\Http\Middleware\ScopeToProvider;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

if (! function_exists('apiResponse')) {
    /**
     * Standard SEVEN API response envelope.
     *
     * Every endpoint returns the same shape:
     * {
     *   success: bool,
     *   message: string,
     *   data: mixed,
     *   errors: array|null,
     *   meta: array|null
     * }
     */
    function apiResponse(
        bool $success = true,
        string $message = '',
        mixed $data = null,
        ?array $errors = null,
        ?array $meta = null,
        int $status = 200
    ): JsonResponse {
        $payload = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null || $success === true) {
            $payload['data'] = $data;
        }

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }
}

if (! function_exists('apiSuccess')) {
    /**
     * Shortcut for a successful response.
     */
    function apiSuccess(mixed $data = null, string $message = 'OK', ?array $meta = null, int $status = 200): JsonResponse
    {
        return apiResponse(true, $message, $data, null, $meta, $status);
    }
}

if (! function_exists('apiError')) {
    /**
     * Shortcut for an error response.
     */
    function apiError(string $message = 'Error', mixed $data = null, ?array $errors = null, int $status = 400): JsonResponse
    {
        return apiResponse(false, $message, $data, $errors, null, $status);
    }
}

if (! function_exists('apiPerPage')) {
    /**
     * Resolve the requested page size for paginated listings, clamped to
     * the configured bounds so invalid input (0, negatives, garbage or
     * huge values) can never produce a division-by-zero or runaway query.
     */
    function apiPerPage(Request $request): int
    {
        return max(
            1,
            min(
                $request->integer('per_page', config('api.pagination.per_page')),
                config('api.pagination.max_per_page'),
            ),
        );
    }
}

if (! function_exists('mediaUrl')) {
    /**
     * Absolute public URL for a media file.
     *
     * - External URLs (http/https/data) are returned untouched.
     * - If MEDIA_CDN_URL is configured -> {MEDIA_CDN_URL}/{path} (CDN delante de S3).
     * - If the media disk is remote (s3) -> Storage::url() (URL real del bucket).
     * - Otherwise -> {APP_URL}/storage/{path} (almacenamiento público local).
     *
     * El disco se resuelve desde `api.media.disk` (MEDIA_DISK) y no desde
     * `filesystems.default` (FILESYSTEM_DISK) porque es el disco donde
     * MediaAdminController sube realmente los archivos. Si divergieran, esta
     * función emitiría URLs de un disco donde el archivo no existe.
     */
    function mediaUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        $cdn = config('app.media_url');

        if ($cdn !== null && $cdn !== '') {
            return rtrim((string) $cdn, '/').'/'.$path;
        }

        $disk = (string) config('api.media.disk', config('filesystems.default'));

        if (config("filesystems.disks.{$disk}.driver") === 's3') {
            return Storage::disk($disk)->url($path);
        }

        return rtrim(config('app.url'), '/').'/storage/'.$path;
    }
}

if (! function_exists('mediaUrls')) {
    /**
     * Aplica mediaUrl() a cada elemento de una lista de rutas.
     *
     * Para los campos `images` de business/accommodation/experience, que se
     * guardan como array JSON de rutas relativas. Las entradas nulas o vacías
     * se descartan para no dejar huecos en las galerías del cliente.
     */
    function mediaUrls(mixed $paths): array
    {
        if (! is_array($paths)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($path) => mediaUrl(is_string($path) ? $path : null),
            $paths,
        )));
    }
}

if (! function_exists('tenantProvider')) {
    /**
     * Proveedor del tenant activo en el panel /provider/* (lo inyecta el
     * middleware provider.scope). Devuelve null en /admin/*, en la API
     * pública y en cualquier petición sin ese middleware, que es justo el
     * caso sin scoping: el backoffice global ve todo.
     */
    function tenantProvider(): ?Provider
    {
        $provider = request()->attributes->get(ScopeToProvider::ATTR_PROVIDER);

        return $provider instanceof Provider ? $provider : null;
    }
}
