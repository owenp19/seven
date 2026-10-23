<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\BannerResource;
use App\Models\Banner;
use App\Support\CatalogCache;

class BannerController extends BaseController
{
    /**
     * GET /api/banners — active banners within their date range.
     *
     * @OA\Get(
     *     path="/banners",
     *     operationId="listBanners",
     *     tags={"Banners"},
     *     summary="Lista los banners activos",
     *     description="Devuelve todos los banners activos dentro de su rango de fechas (start_date / end_date).",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Banners obtenidos correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Banners retrieved successfully."),
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Marea de ofertas"),
     *                     @OA\Property(property="subtitle", type="string", example="Promociones para tu visita"),
     *                     @OA\Property(property="description", type="string", example="Las mejores experiencias de San Andrés a precios especiales."),
     *                     @OA\Property(property="image", type="string", example="http://localhost:8000/storage/banners/playa-san-luis.jpg"),
     *                     @OA\Property(property="button_text", type="string", example="Ver ofertas"),
     *                     @OA\Property(property="button_url", type="string", example="https://seven.app/promotions"),
     *                     @OA\Property(property="status", type="string", enum={"active","inactive","scheduled","expired"}, example="active"),
     *                     @OA\Property(property="order", type="integer", example=1),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2026-09-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2026-12-31"),
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
        $banners = CatalogCache::banners(fn () => Banner::query()->active()->get());

        return $this->success(
            data: BannerResource::collection($banners),
            message: 'Banners retrieved successfully.',
        );
    }
}
