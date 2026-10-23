<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\AccommodationResource;
use App\Repositories\AccommodationRepository;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;

class AccommodationController extends BaseController
{
    public function __construct(
        private readonly AccommodationRepository $accommodations,
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * GET /api/accommodations?guests=4&q=playa — search paginated accommodations.
     *
     * @OA\Get(
     *     path="/accommodations",
     *     operationId="listAccommodations",
     *     tags={"Alojamientos"},
     *     summary="Busca alojamientos",
     *     description="Busca alojamientos aprobados por capacidad (guests) y texto libre. Resultados paginados.",
     *
     *     @OA\Parameter(
     *         name="guests",
     *         in="query",
     *         required=false,
     *         description="Capacidad mínima de huéspedes.",
     *
     *         @OA\Schema(type="integer", example=4)
     *     ),
     *
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=false,
     *         description="Búsqueda de texto libre.",
     *
     *         @OA\Schema(type="string", example="playa")
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
     *         description="Alojamientos obtenidos correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Accommodations retrieved successfully."),
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
        $result = $this->accommodations->search([
            'guests' => $request->input('guests'),
            'q' => $request->input('q'),
            'per_page' => apiPerPage($request),
        ]);

        return $this->success(
            data: AccommodationResource::collection($result),
            message: 'Accommodations retrieved successfully.',
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
     * GET /api/accommodations/{accommodation} — accommodation detail.
     *
     * @OA\Get(
     *     path="/accommodations/{accommodation}",
     *     operationId="showAccommodation",
     *     tags={"Alojamientos"},
     *     summary="Consulta un alojamiento por id",
     *     description="Devuelve el perfil de un alojamiento aprobado.",
     *
     *     @OA\Parameter(
     *         name="accommodation",
     *         in="path",
     *         required=true,
     *         description="ID del alojamiento.",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Alojamiento obtenido correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Accommodation retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Alojamiento no encontrado o no aprobado."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function show(int $id)
    {
        $accommodation = $this->accommodations->query()->approved()->findOrFail($id);

        return $this->success(
            data: new AccommodationResource($accommodation),
            message: 'Accommodation retrieved successfully.',
        );
    }

    /**
     * GET /api/accommodations/{accommodation}/availability?date=2026-10-01 — availability for a date.
     *
     * @OA\Get(
     *     path="/accommodations/{accommodation}/availability",
     *     operationId="accommodationAvailability",
     *     tags={"Alojamientos"},
     *     summary="Disponibilidad de un alojamiento",
     *     description="Consultar la disponibilidad de un alojamiento para una fecha concreta.",
     *
     *     @OA\Parameter(
     *         name="accommodation",
     *         in="path",
     *         required=true,
     *         description="ID del alojamiento.",
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
     *     @OA\Response(response=404, description="Alojamiento no encontrado."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function availability(int $id, Request $request)
    {
        // Mismo ámbito que show(): un alojamiento pending/inactive es invisible
        // en el catálogo, así que tampoco debe filtrar precios, capacidad ni
        // ocupación por este endpoint.
        $accommodation = $this->accommodations->query()->approved()->findOrFail($id);
        $date = $request->input('date', now()->toDateString());

        return $this->success(
            data: $this->availability->forAccommodation($accommodation, $date),
            message: 'Availability retrieved successfully.',
        );
    }
}
