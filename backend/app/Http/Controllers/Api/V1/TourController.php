<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\TourResource;
use App\Models\Tour;
use Illuminate\Http\Request;

class TourController extends BaseController
{
    /**
     * GET /api/tours?per_page=10 — approved tours with relationships, paginated.
     *
     * @OA\Get(
     *     path="/tours",
     *     operationId="listTours",
     *     tags={"Tours"},
     *     summary="Lista los tours y experiencias aprobados",
     *     description="Devuelve los tours aprobados con imágenes, incluye, recomendaciones y proveedor, paginados.",
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
     *         description="Tours obtenidos correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tours retrieved successfully."),
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="slug", type="string", example="johnny-cay-paseo-en-lancha"),
     *                     @OA\Property(property="name", type="string", example="Johnny Cay, paseo en lancha"),
     *                     @OA\Property(property="subtitle", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="category", type="string", example="boat"),
     *                     @OA\Property(property="location", type="string", example="San Andrés Isla"),
     *                     @OA\Property(property="duration", type="string", example="5 horas"),
     *                     @OA\Property(property="price", type="integer", example=120000),
     *                     @OA\Property(property="rating", type="number", format="float", example=4.8),
     *                     @OA\Property(property="reviews", type="integer", example=1300),
     *                     @OA\Property(property="cover_image", type="string"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="images", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="includes", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="recommendations", type="array", @OA\Items(type="object")),
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

        $result = Tour::query()
            ->approved()
            ->with(['images', 'includes', 'recommendations', 'provider'])
            ->orderBy('id')
            ->paginate($perPage);

        return $this->success(
            data: TourResource::collection($result),
            message: 'Tours retrieved successfully.',
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
     * GET /api/tours/{slug} — full tour profile with all relationships.
     *
     * @OA\Get(
     *     path="/tours/{slug}",
     *     operationId="showTour",
     *     tags={"Tours"},
     *     summary="Consulta un tour por slug",
     *     description="Devuelve el perfil completo de un tour aprobado con todas sus relaciones.",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug del tour (ej: johnny-cay-paseo-en-lancha).",
     *
     *         @OA\Schema(type="string", example="johnny-cay-paseo-en-lancha")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tour obtenido correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tour retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="slug", type="string", example="johnny-cay-paseo-en-lancha"),
     *                 @OA\Property(property="name", type="string", example="Johnny Cay, paseo en lancha"),
     *                 @OA\Property(property="subtitle", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="category", type="string"),
     *                 @OA\Property(property="location", type="string"),
     *                 @OA\Property(property="duration", type="string"),
     *                 @OA\Property(property="price", type="integer"),
     *                 @OA\Property(property="rating", type="number", format="float"),
     *                 @OA\Property(property="reviews", type="integer"),
     *                 @OA\Property(property="cover_image", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="images", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="includes", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="recommendations", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="provider", type="object"),
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Tour no encontrado o no aprobado."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function show(string $slug)
    {
        $tour = Tour::query()
            ->approved()
            ->with(['images', 'includes', 'recommendations', 'provider'])
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->success(
            data: new TourResource($tour),
            message: 'Tour retrieved successfully.',
        );
    }
}
