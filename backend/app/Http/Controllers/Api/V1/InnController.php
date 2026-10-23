<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\InnResource;
use App\Models\Inn;
use Illuminate\Http\Request;

class InnController extends BaseController
{
    /**
     * GET /api/inns?per_page=10 — approved native inns with rooms, paginated.
     *
     * @OA\Get(
     *     path="/inns",
     *     operationId="listInns",
     *     tags={"Posadas nativas"},
     *     summary="Lista las posadas nativas aprobadas",
     *     description="Devuelve las posadas nativas aprobadas con sus habitaciones y proveedor, paginadas.",
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
     *         description="Posadas obtenidas correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Inns retrieved successfully."),
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="slug", type="string", example="posada-miss-trinie"),
     *                     @OA\Property(property="name", type="string", example="Posada Miss Trinie"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="cover_image", type="string"),
     *                     @OA\Property(property="location", type="string"),
     *                     @OA\Property(property="price", type="integer"),
     *                     @OA\Property(property="services", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="availability", type="boolean"),
     *                     @OA\Property(property="rating", type="number", format="float"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="rooms", type="array", @OA\Items(type="object")),
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

        $result = Inn::query()
            ->approved()
            ->with(['rooms', 'provider'])
            ->orderBy('id')
            ->paginate($perPage);

        return $this->success(
            data: InnResource::collection($result),
            message: 'Inns retrieved successfully.',
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
     * GET /api/inns/{slug} — native inn with its rooms.
     *
     * @OA\Get(
     *     path="/inns/{slug}",
     *     operationId="showInn",
     *     tags={"Posadas nativas"},
     *     summary="Consulta una posada por slug",
     *     description="Devuelve el perfil de una posada nativa aprobada con sus habitaciones.",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug de la posada (ej: posada-miss-trinie).",
     *
     *         @OA\Schema(type="string", example="posada-miss-trinie")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Posada obtenida correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Inn retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="cover_image", type="string"),
     *                 @OA\Property(property="location", type="string"),
     *                 @OA\Property(property="price", type="integer"),
     *                 @OA\Property(property="services", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="availability", type="boolean"),
     *                 @OA\Property(property="rating", type="number", format="float"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="rooms", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="provider", type="object"),
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Posada no encontrada o no aprobada."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function show(string $slug)
    {
        $inn = Inn::query()
            ->approved()
            ->with(['rooms', 'provider'])
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->success(
            data: new InnResource($inn),
            message: 'Inn retrieved successfully.',
        );
    }
}
