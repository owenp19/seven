<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\PromotionResource;
use App\Models\Promotion;
use App\Support\CatalogCache;

class PromotionController extends BaseController
{
    /**
     * GET /api/promotions — active promotions with their provider.
     *
     * @OA\Get(
     *     path="/promotions",
     *     operationId="listPromotions",
     *     tags={"Promociones"},
     *     summary="Lista las promociones activas",
     *     description="Devuelve todas las promociones activas junto con su proveedor.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Promociones obtenidas correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Promotions retrieved successfully."),
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="provider_id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Tour a Johnny Cay con 20% OFF"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="image", type="string"),
     *                     @OA\Property(property="discount", type="integer", example=20),
     *                     @OA\Property(property="price", type="integer", nullable=true, example=96000),
     *                     @OA\Property(property="start_date", type="string", format="date"),
     *                     @OA\Property(property="end_date", type="string", format="date"),
     *                     @OA\Property(property="status", type="string", enum={"active","inactive","expired"}, example="active"),
     *                     @OA\Property(property="provider", type="object", @OA\Property(property="id", type="integer")),
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
    public function index()
    {
        $promotions = CatalogCache::promotions(
            fn () => Promotion::query()
                ->active()
                ->with('provider')
                ->get(),
        );

        return $this->success(
            data: PromotionResource::collection($promotions),
            message: 'Promotions retrieved successfully.',
        );
    }
}
