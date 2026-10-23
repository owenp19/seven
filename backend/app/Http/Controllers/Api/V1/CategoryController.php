<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\CategoryResource;
use App\Repositories\CategoryRepository;
use App\Support\CatalogCache;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    public function __construct(private readonly CategoryRepository $categories) {}

    /**
     * GET /api/categories — active marketplace categories.
     *
     * @OA\Get(
     *     path="/categories",
     *     operationId="listCategories",
     *     tags={"Categorías"},
     *     summary="Lista las categorías activas",
     *     description="Devuelve las categorías activas del marketplace con su icono.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Categorías obtenidas correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Categories retrieved successfully."),
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="slug", type="string", example="tours"),
     *                     @OA\Property(property="name", type="string", example="Tours y experiencias"),
     *                     @OA\Property(property="icon", type="string", example="boat-outline"),
     *                     @OA\Property(property="image", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="sort_order", type="integer", example=1),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="business_count", type="integer", example=3),
     *                 )
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function index(Request $request)
    {
        $result = CatalogCache::categories(
            'index',
            fn () => $this->categories->activeCategories()->get(),
        );

        return $this->success(
            data: CategoryResource::collection($result),
            message: 'Categories retrieved successfully.',
        );
    }

    /**
     * GET /api/categories/{slug} — category with its approved businesses.
     *
     * @OA\Get(
     *     path="/categories/{slug}",
     *     operationId="showCategory",
     *     tags={"Categorías"},
     *     summary="Consulta una categoría",
     *     description="Devuelve una categoría activa junto con sus negocios aprobados.",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug de la categoría (ej: tours, restaurantes, posadas-nativas, panaderias).",
     *
     *         @OA\Schema(type="string", example="tours")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Categoría obtenida correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="slug", type="string", example="tours"),
     *                 @OA\Property(property="name", type="string", example="Tours y experiencias"),
     *                 @OA\Property(property="icon", type="string", example="boat-outline"),
     *                 @OA\Property(property="image", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="sort_order", type="integer"),
     *                 @OA\Property(property="order", type="integer"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="business_count", type="integer"),
     *                 @OA\Property(property="businesses", type="array", @OA\Items(type="object")),
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Categoría no encontrada."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function show(string $slug)
    {
        $category = CatalogCache::categories(
            'show:'.$slug,
            fn () => $this->categories->query()
                ->active()
                ->where('slug', $slug)
                ->withCount('businesses')
                ->with('businesses', fn ($q) => $q->approved())
                ->firstOrFail(),
        );

        return $this->success(
            data: new CategoryResource($category),
            message: 'Category retrieved successfully.',
        );
    }
}
