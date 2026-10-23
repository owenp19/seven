<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\ExperienceResource;
use App\Repositories\ExperienceRepository;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;

class ExperienceController extends BaseController
{
    public function __construct(
        private readonly ExperienceRepository $experiences,
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * GET /api/experiences?guests=4&category=tours&q=johnny — search paginated experiences.
     *
     * @OA\Get(
     *     path="/experiences",
     *     operationId="listExperiences",
     *     tags={"Experiencias"},
     *     summary="Busca experiencias",
     *     description="Busca experiencias aprobadas por capacidad, categoría y texto libre. Resultados paginados.",
     *
     *     @OA\Parameter(
     *         name="guests",
     *         in="query",
     *         required=false,
     *         description="Capacidad mínima de participantes.",
     *
     *         @OA\Schema(type="integer", example=4)
     *     ),
     *
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=false,
     *         description="Filtra por categoría.",
     *
     *         @OA\Schema(type="string", example="tours")
     *     ),
     *
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=false,
     *         description="Búsqueda de texto libre.",
     *
     *         @OA\Schema(type="string", example="johnny")
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
     *         description="Experiencias obtenidas correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Experiences retrieved successfully."),
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
        $result = $this->experiences->search([
            'guests' => $request->input('guests'),
            'category' => $request->input('category'),
            'q' => $request->input('q'),
            'per_page' => apiPerPage($request),
        ]);

        return $this->success(
            data: ExperienceResource::collection($result),
            message: 'Experiences retrieved successfully.',
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
     * GET /api/experiences/{experience} — experience detail.
     *
     * @OA\Get(
     *     path="/experiences/{experience}",
     *     operationId="showExperience",
     *     tags={"Experiencias"},
     *     summary="Consulta una experiencia por id",
     *     description="Devuelve el perfil de una experiencia aprobada.",
     *
     *     @OA\Parameter(
     *         name="experience",
     *         in="path",
     *         required=true,
     *         description="ID de la experiencia.",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Experiencia obtenida correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Experience retrieved successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *             ),
     *             @OA\Property(property="errors", type="object", example={}),
     *             @OA\Property(property="meta", type="object", example={}),
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Experiencia no encontrada o no aprobada."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function show(int $id)
    {
        $experience = $this->experiences->query()->approved()->findOrFail($id);

        return $this->success(
            data: new ExperienceResource($experience),
            message: 'Experience retrieved successfully.',
        );
    }

    /**
     * GET /api/experiences/{experience}/availability?date=2026-10-01 — availability for a date.
     *
     * @OA\Get(
     *     path="/experiences/{experience}/availability",
     *     operationId="experienceAvailability",
     *     tags={"Experiencias"},
     *     summary="Disponibilidad de una experiencia",
     *     description="Consultar la disponibilidad de una experiencia para una fecha concreta.",
     *
     *     @OA\Parameter(
     *         name="experience",
     *         in="path",
     *         required=true,
     *         description="ID de la experiencia.",
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
     *     @OA\Response(response=404, description="Experiencia no encontrada."),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite 120/min).")
     * )
     */
    public function availability(int $id, Request $request)
    {
        // Mismo ámbito que show(): una experiencia pending/inactive es invisible
        // en el catálogo, así que tampoco debe filtrar precios, capacidad ni
        // ocupación por este endpoint.
        $experience = $this->experiences->query()->approved()->findOrFail($id);
        $date = $request->input('date', now()->toDateString());

        return $this->success(
            data: $this->availability->forExperience($experience, $date),
            message: 'Availability retrieved successfully.',
        );
    }
}
