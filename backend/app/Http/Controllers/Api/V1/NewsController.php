<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\NewsResource;
use App\Models\News;
use App\Support\CatalogCache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Noticias de la isla.
 *
 * `GET /api/news` es el feed de la pantalla Noticias: solo lo publicado y ya
 * vigente, que es lo unico que puede aparecer en una app sin login. `show`
 * resuelve por slug y no por id para que una nota se pueda compartir por
 * enlace.
 */
class NewsController extends BaseController
{
    /**
     * GET /api/news — noticias publicadas, más recientes primero.
     *
     * @OA\Get(
     *     path="/news",
     *     operationId="listNews",
     *     tags={"Noticias"},
     *     summary="Lista las noticias publicadas",
     *     description="Devuelve las noticias publicadas cuya fecha de publicación ya pasó, ordenadas por destacado y fecha.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Noticias obtenidas correctamente.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="News retrieved successfully."),
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="San Andrés celebra el Festival de la Arepa de Huevo"),
     *                     @OA\Property(property="slug", type="string", example="festival-arepa-huevo-2026"),
     *                     @OA\Property(property="excerpt", type="string"),
     *                     @OA\Property(property="body", type="string", nullable=true),
     *                     @OA\Property(property="image", type="string"),
     *                     @OA\Property(property="kind", type="string", enum={"news","event","tip","promotion"}, example="event"),
     *                     @OA\Property(property="link_url", type="string", nullable=true),
     *                     @OA\Property(property="status", type="string", enum={"published","draft","archived"}, example="published"),
     *                     @OA\Property(property="is_featured", type="boolean", example=true),
     *                     @OA\Property(property="published_at", type="string", format="date-time"),
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
        $news = CatalogCache::news(
            fn () => News::query()
                ->published()
                ->ordered()
                ->with('provider')
                ->get(),
        );

        return $this->success(
            data: NewsResource::collection($news),
            message: 'News retrieved successfully.',
        );
    }

    /**
     * GET /api/news/{slug} — una noticia por su slug.
     *
     * @OA\Get(
     *     path="/news/{slug}",
     *     operationId="getNews",
     *     tags={"Noticias"},
     *     summary="Detalle de una noticia",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, type="string", example="festival-arepa-huevo-2026"),
     *
     *     @OA\Response(response=200, description="Noticia obtenida correctamente."),
     *     @OA\Response(response=404, description="La noticia no existe o no está publicada.")
     * )
     */
    public function show(string $slug)
    {
        $news = News::query()
            ->published()
            ->with('provider')
            ->where('slug', $slug)
            ->first();

        // Un borrador responde 404 y no 403 a propósito: la app no tiene login,
        // así que distinguir "existe pero no lo puedes ver" solo sirve para
        // confirmarle al curioso que ese slug está reservado.
        if ($news === null) {
            throw new NotFoundHttpException('News not found.');
        }

        return $this->success(
            data: NewsResource::make($news),
            message: 'News retrieved successfully.',
        );
    }
}
