<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\V1\CommentResource;
use App\Models\Comment;
use App\Models\Inn;
use App\Models\Restaurant;
use App\Models\Tour;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CommentAdminController
 *
 * Moderación de comentarios desde el backoffice.
 *
 * No extiende `AdminCrudController` como el resto del catálogo porque un
 * comentario no se crea ni se edita desde el panel: llega desde el público y
 * lo único que se hace con él es cambiarle el estado o borrarlo. El token
 * compartido y el `throttle:admin` los pone el grupo de rutas de `admin`.
 */
class CommentAdminController extends BaseController
{
    /**
     * GET /api/admin/comments?status=pending — listado con filtro por estado.
     *
     * Por defecto trae `pending`: la cola de trabajo de quien modera es lo
     * que hay que ver al entrar, no el feed público que ya funciona solo.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', 'nullable', Rule::in(Comment::statuses())],
            'type' => ['sometimes', 'nullable', Rule::in(['tour', 'restaurant', 'inn'])],
        ]);

        $comments = Comment::query()
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->when($request->filled('type'), fn ($query) => $query->where(
                'commentable_type',
                $this->modelClass($request->string('type')->toString()),
            ))
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return $this->success(
            data: CommentResource::collection($comments),
            message: 'Comments retrieved successfully.',
        );
    }

    /**
     * PATCH /api/admin/comments/{comment}/status — publica, oculta o envía a revisión.
     */
    public function updateStatus(Request $request, Comment $comment): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(Comment::statuses())],
        ]);

        $previousStatus = $comment->status;

        $comment->update([
            'status' => $request->string('status')->toString(),
        ]);

        app(AuditLogger::class)->recordFor($request, 'comment.status_changed', $comment, [
            'from' => $previousStatus,
            'to' => $comment->status,
        ]);

        return $this->success(
            data: new CommentResource($comment->refresh()),
            message: 'Comment status updated successfully.',
        );
    }

    /**
     * DELETE /api/admin/comments/{comment} — borrado definitivo.
     *
     * Borra de verdad, sin soft delete: un comentario es texto anónimo y
     * no hay nada que conservar. Como la unicidad es por dispositivo,
     * borrarlo además libera ese comentario para el mismo dispositivo.
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $id = (int) $comment->id;

        $comment->delete();

        app(AuditLogger::class)->recordFor($request, 'comment.deleted', null, [
            'comment_id' => $id,
        ]);

        return $this->success(
            data: ['id' => $id],
            message: 'Comment deleted successfully.',
        );
    }

    /**
     * Traduce la etiqueta de la API a la FQCN que guarda la columna
     * polimórfica.
     *
     * @return class-string
     */
    private function modelClass(string $type): string
    {
        return match ($type) {
            'tour' => Tour::class,
            'restaurant' => Restaurant::class,
            'inn' => Inn::class,
            default => Comment::class,
        };
    }
}
