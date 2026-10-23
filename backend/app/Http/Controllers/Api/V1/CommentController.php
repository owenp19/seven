<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\CommentResource;
use App\Models\Comment;
use App\Models\Inn;
use App\Models\Restaurant;
use App\Models\Tour;
use App\Services\BookingOwnershipService;
use App\Services\CommentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CommentController
 *
 * Comentarios de un destino, uno por dispositivo.
 *
 * Hay un par de rutas por vertical en vez de una genérica `/comments` porque
 * cada una puede resolver el slug con su propio modelo y así reutilizar el
 * `firstOrFail()` de siempre: un destino inexistente o no aprobado responde
 * 404 con el mismo mensaje que el resto del catálogo, sin código nuevo.
 */
class CommentController extends BaseController
{
    public function __construct(
        private readonly CommentService $comments,
        private readonly BookingOwnershipService $ownership,
    ) {}

    /**
     * GET /api/tours/{slug}/comments — opiniones publicadas de un tour.
     *
     * @OA\Get(
     *     path="/tours/{slug}/comments",
     *     operationId="listTourComments",
     *     tags={"Comentarios"},
     *     summary="Lista los comentarios publicados de un tour",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug del tour (ej: vuelta-a-la-isla-en-chiva).",
     *
     *         @OA\Schema(type="string", example="vuelta-a-la-isla-en-chiva")
     *     ),
     *
     *     @OA\Response(response=200, description="Comentarios obtenidos correctamente."),
     *     @OA\Response(response=404, description="Tour no encontrado o no aprobado.")
     * )
     */
    public function tourIndex(string $tour): JsonResponse
    {
        return $this->indexFor($this->approvedBySlug(Tour::class, $tour));
    }

    /**
     * POST /api/tours/{slug}/comments — publica la opinión de este dispositivo.
     *
     * @OA\Post(
     *     path="/tours/{slug}/comments",
     *     operationId="storeTourComment",
     *     tags={"Comentarios"},
     *     summary="Publica un comentario en un tour",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug del tour.",
     *
     *         @OA\Schema(type="string", example="vuelta-a-la-isla-en-chiva")
     *     ),
     *
     *     @OA\Parameter(
     *         name="X-Install-Id",
     *         in="header",
     *         required=true,
     *         description="Identificador del dispositivo. Sin él no se puede publicar.",
     *
     *         @OA\Schema(type="string", example="7F3A1C8E-0000-4000-8000-000000000001")
     *     ),
     *
     *     @OA\Response(response=201, description="Comentario publicado."),
     *     @OA\Response(response=403, description="Falta el X-Install-Id."),
     *     @OA\Response(response=422, description="Este dispositivo ya comentó, o los datos no son válidos."),
     *     @OA\Response(response=429, description="Demasiados comentarios desde este dispositivo.")
     * )
     */
    public function tourStore(Request $request, string $tour): JsonResponse
    {
        return $this->storeFor($request, $this->approvedBySlug(Tour::class, $tour));
    }

    /** GET /api/restaurants/{slug}/comments — opiniones publicadas de un restaurante. */
    public function restaurantIndex(string $restaurant): JsonResponse
    {
        return $this->indexFor($this->approvedBySlug(Restaurant::class, $restaurant));
    }

    /** POST /api/restaurants/{slug}/comments — publica la opinión de este dispositivo. */
    public function restaurantStore(Request $request, string $restaurant): JsonResponse
    {
        return $this->storeFor($request, $this->approvedBySlug(Restaurant::class, $restaurant));
    }

    /** GET /api/inns/{slug}/comments — opiniones publicadas de una posada. */
    public function innIndex(string $inn): JsonResponse
    {
        return $this->indexFor($this->approvedBySlug(Inn::class, $inn));
    }

    /** POST /api/inns/{slug}/comments — publica la opinión de este dispositivo. */
    public function innStore(Request $request, string $inn): JsonResponse
    {
        return $this->storeFor($request, $this->approvedBySlug(Inn::class, $inn));
    }

    private function indexFor(Model $commentable): JsonResponse
    {
        return $this->success(
            data: CommentResource::collection($this->comments->publishedFor($commentable)),
            message: 'Comments retrieved successfully.',
        );
    }

    /**
     * Valida el cuerpo, exige la identidad del dispositivo y guarda.
     *
     * `requireDeviceScope()` es el mismo guardia que usan las reservas: si no
     * se puede saber de quién es el comentario, no se acepta. Un comentario
     * anónimo sería indistinguible del spam, que es justo lo que este endpoint
     * intenta evitar.
     */
    private function storeFor(Request $request, Model $commentable): JsonResponse
    {
        $installId = $this->ownership->requireDeviceScope($request);

        $data = $request->validate([
            'author_name' => ['required', 'string', 'min:2', 'max:80'],
            'body' => ['required', 'string', 'min:10', 'max:1000'],
            'rating' => ['sometimes', 'nullable', 'integer', 'between:1,5'],
        ], [
            'author_name.required' => 'Escribe tu nombre para firmar el comentario.',
            'author_name.min' => 'El nombre necesita al menos 2 caracteres.',
            'body.required' => 'Escribe tu opinión antes de publicar.',
            'body.min' => 'La opinión necesita al menos 10 caracteres.',
            'body.max' => 'La opinión no puede pasar de 1000 caracteres.',
            'rating.between' => 'La valoración debe ir de 1 a 5 estrellas.',
        ]);

        // Queda registrado para poder auditar cuánta gente sigue cayendo en
        // el campo trampa.
        if ($this->filledHoneypot($request)) {
            Log::info('Comentario descartado por honeypot.', [
                'commentable_type' => $commentable::class,
                'commentable_id' => $commentable->getKey(),
            ]);

            // Se responde como si se hubiera guardado: un bot que recibe un
            // error aprende que el campo es un filtro, y uno que recibe la
            // misma respuesta que un envío legítimo no tiene por qué
            // reintentar con otro camino.
            //
            // Se devuelve lo que el visitante escribió en lugar de un objeto
            // vacío: si la respuesta fuera la misma pero con los campos a null,
            // el frontend se encontraría con un comentario sin autor, y un
            // autocompletador del sistema que rellene el campo trampa
            // —cosa habitual en Android— rompería la pantalla en lugar de
            // escribir una reseña.
            return $this->success(
                data: new CommentResource(new Comment([
                    ...$data,
                    'status' => Comment::PUBLISHED,
                ])),
                message: 'Comment submitted successfully.',
                status: 201,
            );
        }

        $comment = $this->comments->storeFor($commentable, $installId, $data);

        return $this->success(
            data: new CommentResource($comment),
            message: 'Comment submitted successfully.',
            status: 201,
        );
    }

    /**
     * Destino aprobado por slug. Se resuelve por `approved()` para que un
     * comentario nunca sea la vía de entrada a una ficha que el catálogo no
     * muestra.
     *
     * @param  class-string<Model>  $model
     */
    private function approvedBySlug(string $model, string $slug): Model
    {
        return $model::approved()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Campo trampa. Los robots rellenan todos los inputs que encuentran;
     * los humanos no ven un `input` oculto ni lo completan.
     */
    private function filledHoneypot(Request $request): bool
    {
        return is_string($request->input('website')) && trim($request->input('website')) !== '';
    }
}
