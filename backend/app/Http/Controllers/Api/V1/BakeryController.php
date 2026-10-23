<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\BakeryResource;
use App\Models\Bakery;
use Illuminate\Http\Request;

class BakeryController extends BaseController
{
    /**
     * GET /api/bakeries?per_page=10 — approved bakeries with products, paginated.
     *
     * @OA\Get(
     *     path="/bakeries",
     *     operationId="listBakeries",
     *     tags={"Panaderías"},
     *     summary="Lista las panaderías y cafeterías aprobadas",
     *     description="Devuelve las panaderías aprobadas con su catálogo de productos y proveedor, paginadas.",
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Cantidad de resultados por página (máx. 100).",
     *
     *         @OA\Schema(type="integer", default=15, example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Panaderías obtenidas correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bakeries retrieved successfully."),
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="slug", type="string", example="panaderia-suzette"),
     *                     @OA\Property(property="name", type="string", example="Panadería Suzette"),
     *                     @OA\Property(property="logo", type="string"),
     *                     @OA\Property(property="cover_image", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="address", type="string"),
     *                     @OA\Property(property="phone", type="string"),
     *                     @OA\Property(property="category", type="string"),
     *                     @OA\Property(property="rating", type="number", format="float"),
     *                     @OA\Property(property="reviews", type="integer"),
     *                     @OA\Property(property="opening_hours", type="string"),
     *                     @OA\Property(property="price_range", type="string"),
     *                     @OA\Property(property="location", type="object",
     *                         @OA\Property(property="latitude", type="number"),
     *                         @OA\Property(property="longitude", type="number"),
     *                     ),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="products", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="provider", type="object"),
     *                 )
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="total", type="integer", example=3),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="last_page", type="integer", example=1),
     *                 )
     *             ),
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function index(Request $request)
    {
        $perPage = apiPerPage($request);

        $result = Bakery::query()
            ->approved()
            ->with(['products', 'provider'])
            ->orderBy('id')
            ->paginate($perPage);

        return $this->success(
            data: BakeryResource::collection($result),
            message: 'Bakeries retrieved successfully.',
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
     * GET /api/bakeries/{slug} — bakery profile with its catalog.
     *
     * @OA\Get(
     *     path="/bakeries/{slug}",
     *     operationId="showBakery",
     *     tags={"Panaderías"},
     *     summary="Consulta una panadería por slug",
     *     description="Devuelve el perfil de una panadería aprobada con su catálogo de productos.",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug de la panadería (ej: panaderia-suzette).",
     *
     *         @OA\Schema(type="string", example="panaderia-suzette")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Panadería obtenida correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bakery retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="logo", type="string"),
     *                 @OA\Property(property="cover_image", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="category", type="string"),
     *                 @OA\Property(property="rating", type="number", format="float"),
     *                 @OA\Property(property="reviews", type="integer"),
     *                 @OA\Property(property="opening_hours", type="string"),
     *                 @OA\Property(property="price_range", type="string"),
     *                 @OA\Property(property="location", type="object"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="products", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="provider", type="object"),
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Panadería no encontrada o no aprobada."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function show(string $slug)
    {
        $bakery = Bakery::query()
            ->approved()
            ->with(['products', 'provider'])
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->success(
            data: new BakeryResource($bakery),
            message: 'Bakery retrieved successfully.',
        );
    }
}
