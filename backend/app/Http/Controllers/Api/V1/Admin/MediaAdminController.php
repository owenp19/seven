<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\MediaResource;
use App\Models\Accommodation;
use App\Models\Bakery;
use App\Models\BakeryProduct;
use App\Models\Banner;
use App\Models\Business;
use App\Models\Category;
use App\Models\Experience;
use App\Models\Inn;
use App\Models\InnRoom;
use App\Models\Promotion;
use App\Models\Provider;
use App\Models\Restaurant;
use App\Models\RestaurantProduct;
use App\Models\Tour;
use App\Models\TourImage;
use App\Repositories\MediaRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * MediaAdminController
 *
 * Gestión de archivos (imágenes/video) para el catálogo:
 * - GET    /api/v1/admin/media        → lista paginada y filtrable
 * - GET    /api/v1/admin/media/{id}   → detalle con su vínculo morfo
 * - POST   /api/v1/admin/media        → sube un archivo al disco de media
 * - PATCH  /api/v1/admin/media/{id}   → cambia type/order/vínculo morfo
 * - DELETE /api/v1/admin/media/{id}   → elimina archivo y registro
 *
 * Disco de destino: config('api.media.disk') ("public" dev / "s3" prod).
 */
class MediaAdminController extends BaseController
{
    private const ALLOWED_MODELS = [
        Category::class,
        Banner::class,
        Promotion::class,
        Business::class,
        Tour::class,
        TourImage::class,
        Inn::class,
        InnRoom::class,
        Restaurant::class,
        RestaurantProduct::class,
        Bakery::class,
        BakeryProduct::class,
        Accommodation::class,
        Experience::class,
        Provider::class,
    ];

    public function __construct(private readonly MediaRepository $media) {}

    /**
     * GET /api/v1/admin/media — list media, newest first.
     *
     * Filtros: `type`, `model_type`, `model_id`.
     */
    public function index(Request $request)
    {
        $query = $this->media->query()->with('model');

        if (is_string($type = $request->input('type')) && $type !== '') {
            $query->where('type', $type);
        }

        if (is_string($modelType = $request->input('model_type')) && $modelType !== '') {
            $query->where('model_type', $modelType);
        }

        if ($request->filled('model_id')) {
            $query->where('model_id', (int) $request->input('model_id'));
        }

        $result = $query->latest()->paginate(apiPerPage($request));

        return $this->success(
            data: MediaResource::collection($result),
            message: 'Media list retrieved successfully.',
            meta: [
                'pagination' => [
                    'total' => $result->total(),
                    'per_page' => $result->perPage(),
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                ],
            ],
        );
    }

    /**
     * GET /api/v1/admin/media/{id} — single media record.
     */
    public function show(int $id)
    {
        $media = $this->media->query()->with('model')->findOrFail($id);

        return $this->success(
            data: new MediaResource($media),
            message: 'Media retrieved successfully.',
        );
    }

    /**
     * POST /api/v1/admin/media — upload a media file.
     *
     *     @OA\PathItem(
     *         path="/admin/media",
     *
     *         @OA\Post(
     *             operationId="storeAdminMedia",
     *             tags={"Administración"},
     *             summary="Sube un archivo de media",
     *
     *             @OA\RequestBody(
     *                 required=true,
     *
     *                 @OA\MediaType(
     *                     mediaType="multipart/form-data",
     *
     *                     @OA\Schema(
     *                         required={"file"},
     *
     *                         @OA\Property(property="file", type="string", format="binary"),
     *                         @OA\Property(property="type", type="string", enum={"image","logo","cover","gallery","product","banner","promotion","video"}),
     *                         @OA\Property(property="order", type="integer", example=0),
     *                     )
     *                 )
     *             ),
     *
     *             @OA\Response(response=201, description="Media creado correctamente."),
     *             @OA\Response(response=422, description="Error de validación.")
     *         )
     *     )
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->storeRules());

        $this->assertTenantCanAttach($data);

        $file = $request->file('file');

        $path = $file->store('media/'.date('Y/m'), $this->disk());

        $media = $this->media->create([
            'provider_id' => $this->resolveProviderId($data),
            'model_type' => $data['model_type'] ?? null,
            'model_id' => $data['model_id'] ?? null,
            'file_path' => $path,
            'type' => $data['type'] ?? 'image',
            'order' => $data['order'] ?? 0,
        ]);

        return $this->success(
            data: new MediaResource($media),
            message: 'Media uploaded successfully.',
            status: 201,
        );
    }

    /**
     * PATCH /api/v1/admin/media/{id} — update media metadata.
     */
    public function update(int $id, Request $request)
    {
        $media = $this->media->findOrFail($id);

        $data = $request->validate($this->metadataRules());

        $this->assertTenantCanAttach($data);

        $this->media->update($id, array_merge($data, [
            'provider_id' => $this->resolveProviderId($data, $media->provider_id),
        ]));

        return $this->success(
            data: new MediaResource($this->media->findOrFail($id)),
            message: 'Media updated successfully.',
        );
    }

    /**
     * DELETE /api/v1/admin/media/{id} — remove media and its file.
     */
    public function destroy(int $id)
    {
        $media = $this->media->findOrFail($id);

        Storage::disk($this->disk())->delete($media->file_path);
        $media->delete();

        return $this->success(
            message: 'Media deleted successfully.',
        );
    }

    private function disk(): string
    {
        return (string) config('api.media.disk', 'public');
    }

    /**
     * Allowlist de anclas del media. El proveedor solo cuelga archivos de
     * SUS verticales y de su propia ficha; categories, banners, products e
     * imágenes de detalle siguen siendo potestad del backoffice.
     */
    private function seedModels(): array
    {
        return tenantProvider() !== null
            ? array_merge(Provider::serviceModels(), [Provider::class])
            : self::ALLOWED_MODELS;
    }

    private function storeRules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.(int) config('api.media.max_kb', 5120),
                // Lista en config/api.php: `svg` queda excluido a propósito,
                // servirse desde el mismo origen lo convierte en XSS
                // almacenado. Ver la nota de `api.media.mimes`.
                'mimes:'.config('api.media.mimes', 'jpeg,png,jpg,webp,gif,avif,mp4,webm'),
            ],
            'type' => ['sometimes', Rule::in(config('api.media.types', ['image']))],
            'order' => ['sometimes', 'integer', 'min:0'],
            'model_type' => ['sometimes', 'nullable', Rule::in($this->seedModels())],
            'model_id' => ['required_with:model_type', 'nullable', 'integer'],
        ];
    }

    private function metadataRules(): array
    {
        return [
            'type' => ['sometimes', Rule::in(config('api.media.types', ['image']))],
            'order' => ['sometimes', 'integer', 'min:0'],
            'model_type' => ['sometimes', 'nullable', Rule::in($this->seedModels())],
            'model_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /**
     * El provider no puede anclar media a un servicio ajeno ni a una ficha
     * que no sea la suya: si el objetivo no es suyo, 422.
     */
    private function assertTenantCanAttach(array $data): void
    {
        $tenant = tenantProvider();

        if ($tenant === null || ($data['model_type'] ?? null) === null) {
            return;
        }

        $modelClass = $data['model_type'];
        $model = $modelClass::find($data['model_id'] ?? null);

        if ($model === null || $this->providerIdFor($model) !== $tenant->id) {
            throw ValidationException::withMessages([
                'model_id' => ['El archivo no puede asociarse a ese recurso.'],
            ]);
        }
    }

    /**
     * Dueño del recurso sobre el que cuelga el archivo: el propio tenant en
     * el panel del proveedor, o el proveedor derivado del morph para el
     * backoffice (NULL si el recurso es de la plataforma o no resuelve).
     */
    private function resolveProviderId(array $data, ?int $fallback = null): ?int
    {
        $tenant = tenantProvider();

        if ($tenant !== null) {
            return $tenant->id;
        }

        $target = $this->resolveMediable($data);

        return $target !== null ? $this->providerIdFor($target) : $fallback;
    }

    private function resolveMediable(array $data): ?object
    {
        $modelClass = $data['model_type'] ?? null;
        $modelId = $data['model_id'] ?? null;

        if ($modelClass === null || $modelId === null || ! class_exists($modelClass)) {
            return null;
        }

        return $modelClass::find($modelId);
    }

    private function providerIdFor(object $model): ?int
    {
        if ($model instanceof Provider) {
            return $model->id;
        }

        $parentRelation = match (class_basename($model)) {
            'TourImage' => 'tour',
            'InnRoom' => 'inn',
            'RestaurantProduct' => 'restaurant',
            'BakeryProduct' => 'bakery',
            default => null,
        };

        $target = $parentRelation !== null ? $model->{$parentRelation} : $model;

        return $target?->provider_id ?? null;
    }
}
