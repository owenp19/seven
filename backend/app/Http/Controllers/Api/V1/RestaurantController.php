<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\RestaurantResource;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends BaseController
{
    /**
     * GET /api/restaurants?per_page=10 — approved restaurants with products, paginated.
     *
     * @OA\Get(
     *     path="/restaurants",
     *     operationId="listRestaurants",
     *     tags={"Restaurantes"},
     *     summary="Lista los restaurantes aprobados",
     *     description="Devuelve los restaurantes aprobados con su menú (productos) y proveedor, paginados.",
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
     *         description="Restaurantes obtenidos correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Restaurants retrieved successfully."),
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="slug", type="string", example="la-regatta"),
     *                     @OA\Property(property="name", type="string", example="La Regatta"),
     *                     @OA\Property(property="logo", type="string"),
     *                     @OA\Property(property="cover_image", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="address", type="string"),
     *                     @OA\Property(property="phone", type="string", example="+57 8 512 0437"),
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

        $result = Restaurant::query()
            ->approved()
            ->with(['products', 'provider'])
            ->orderBy('id')
            ->paginate($perPage);

        return $this->success(
            data: RestaurantResource::collection($result),
            message: 'Restaurants retrieved successfully.',
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
     * GET /api/restaurants/{slug} — restaurant profile with its menu.
     *
     * @OA\Get(
     *     path="/restaurants/{slug}",
     *     operationId="showRestaurant",
     *     tags={"Restaurantes"},
     *     summary="Consulta un restaurante por slug",
     *     description="Devuelve el perfil de un restaurante aprobado con su menú.",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug del restaurante (ej: la-regatta).",
     *
     *         @OA\Schema(type="string", example="la-regatta")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Restaurante obtenido correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Restaurant retrieved successfully."),
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
     *     @OA\Response(response=404, description="Restaurante no encontrado o no aprobado."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function show(string $slug)
    {
        $restaurant = Restaurant::query()
            ->approved()
            ->with(['products', 'provider'])
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->success(
            data: new RestaurantResource($restaurant),
            message: 'Restaurant retrieved successfully.',
        );
    }
}
