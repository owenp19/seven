<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\BusinessResource;
use App\Repositories\BusinessRepository;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;

class BusinessController extends BaseController
{
    public function __construct(
        private readonly BusinessRepository $businesses,
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * GET /api/businesses?category=tours&q=johnny&featured=1 — search paginated businesses.
     *
     * @OA\Get(
     *     path="/businesses",
     *     operationId="listBusinesses",
     *     tags={"Negocios"},
     *     summary="Busca negocios del marketplace",
     *     description="Busca negocios aprobados por categoría, texto libre y/o destacados. Resultados paginados.",
     *
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=false,
     *         description="Filtra por slug de categoría.",
     *
     *         @OA\Schema(type="string", example="tours")
     *     ),
     *
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=false,
     *         description="Búsqueda de texto libre en nombre y descripción.",
     *
     *         @OA\Schema(type="string", example="johnny")
     *     ),
     *
     *     @OA\Parameter(
     *         name="featured",
     *         in="query",
     *         required=false,
     *         description="Devuelve únicamente negocios destacados.",
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
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
     *         description="Negocios obtenidos correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Businesses retrieved successfully."),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="total", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
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
        $result = $this->businesses->search([
            'category' => $request->input('category'),
            'q' => $request->input('q'),
            'featured' => $request->boolean('featured'),
            'per_page' => apiPerPage($request),
        ]);

        return $this->success(
            data: BusinessResource::collection($result),
            message: 'Businesses retrieved successfully.',
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
     * GET /api/businesses/{business} — business detail with its category.
     *
     * @OA\Get(
     *     path="/businesses/{business}",
     *     operationId="showBusiness",
     *     tags={"Negocios"},
     *     summary="Consulta un negocio por id",
     *     description="Devuelve el perfil de un negocio aprobado con su categoría.",
     *
     *     @OA\Parameter(
     *         name="business",
     *         in="path",
     *         required=true,
     *         description="ID del negocio.",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Negocio obtenido correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="category", type="object"),
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Negocio no encontrado o no aprobado."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function show(int $id)
    {
        $business = $this->businesses->query()
            ->with('category')
            ->approved()
            ->findOrFail($id);

        return $this->success(
            data: new BusinessResource($business),
            message: 'Business retrieved successfully.',
        );
    }

    /**
     * GET /api/businesses/{business}/availability?date=2026-10-01 — availability for a date.
     *
     * @OA\Get(
     *     path="/businesses/{business}/availability",
     *     operationId="businessAvailability",
     *     tags={"Negocios"},
     *     summary="Disponibilidad de un negocio",
     *     description="Consultar la disponibilidad de un negocio para una fecha concreta.",
     *
     *     @OA\Parameter(
     *         name="business",
     *         in="path",
     *         required=true,
     *         description="ID del negocio.",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Fecha a consultar (YYYY-MM-DD). Por defecto, hoy.",
     *
     *         @OA\Schema(type="string", format="date", example="2026-10-01")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Disponibilidad obtenida correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Availability retrieved successfully."),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Negocio no encontrado."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function availability(int $id, Request $request)
    {
        // Mismo ámbito que show(): un negocio pending/inactive es invisible en
        // el catálogo, así que tampoco debe filtrar precios, capacidad ni
        // ocupación por este endpoint.
        $business = $this->businesses->query()->approved()->findOrFail($id);
        $date = $request->input('date', now()->toDateString());

        return $this->success(
            data: $this->availability->forBusiness($business, $date),
            message: 'Availability retrieved successfully.',
        );
    }
}
