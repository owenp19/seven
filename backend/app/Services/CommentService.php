<?php

namespace App\Services;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * CommentService
 *
 * Lectura y alta de comentarios públicos de un destino.
 *
 * La regla de negocio que no puede vivir en el controller es la unicidad por
 * dispositivo: la base tiene un índice único en
 * (commentable_type, commentable_id, install_id), y aquí se traduce a un 422
 * legible. Se deja que sea la base la que rechace —el `unique` no se puede
 * eludir con dos peticiones simultáneas— y se captura la violación para
 * devolver un mensaje de "ya opinaste" en lugar de un 500.
 */
final class CommentService
{
    public function __construct(private readonly CommentSpamFilter $spam) {}

    /**
     * Comentarios publicados del destino, del más nuevo al más viejo.
     *
     * Un destino nuevo no puede tener cientos de opiniones, así que el tope es
     * deliberadamente alto: es un límite de seguridad ante el abuso del
     * endpoint, no una paginación. Un slice aquí costaría una consulta extra
     * sin necesidad.
     */
    public function publishedFor(Model $commentable, int $limit = 50): Collection
    {
        return Comment::query()
            ->published()
            ->forTarget($commentable)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Registra un comentario del dispositivo.
     *
     * El estado nace en `published` salvo que el filtro pida revisión; la
     * respuesta nunca revela si se pidió, porque tampoco serviría de nada y
     * solo le enseñaría al remitente cómo calibrar el filtro.
     *
     * @param  array{author_name: string, body: string, rating: int|null}  $data
     *
     * @throws ValidationException si el dispositivo ya comentó antes, o si el
     *                             cuerpo está vacío.
     */
    public function storeFor(Model $commentable, string $installId, array $data): Comment
    {
        $body = trim($data['body']);

        if ($this->alreadyCommented($commentable, $installId)) {
            throw ValidationException::withMessages([
                'body' => ['Ya dejaste tu comentario en esta publicación.'],
            ]);
        }

        [$needsReview, $reasons] = $this->spam->evaluate($body);

        if ($needsReview) {
            Log::info('Comentario enviado a moderación.', [
                'commentable_type' => $commentable::class,
                'commentable_id' => $commentable->getKey(),
                'reasons' => $reasons,
            ]);
        }

        $comment = new Comment([
            'commentable_type' => $commentable::class,
            'commentable_id' => $commentable->getKey(),
            'install_id' => $installId,
            'author_name' => trim($data['author_name']),
            'rating' => $data['rating'] ?? null,
            'body' => $body,
            'status' => $needsReview ? Comment::PENDING : Comment::PUBLISHED,
        ]);

        try {
            $comment->save();
        } catch (QueryException $exception) {
            // Índice único violado: otra petición del mismo dispositivo se
            // coló entre la comprobación y el INSERT.
            if ($this->isUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'body' => ['Ya dejaste tu comentario en esta publicación.'],
                ]);
            }

            throw $exception;
        }

        return $comment;
    }

    private function alreadyCommented(Model $commentable, string $installId): bool
    {
        return Comment::query()
            ->forTarget($commentable)
            ->where('install_id', $installId)
            ->exists();
    }

    /**
     * Detección portable del error de clave duplicada. MySQL y SQLite no
     * comparten el SQLState, pero ambos exponen un índice `unique` en el
     * mensaje, que es lo único que se puede comprobar sin tocar el driver.
     */
    private function isUniqueViolation(QueryException $exception): bool
    {
        return Str::contains(
            Str::lower($exception->getMessage()),
            ['unique', 'duplicate entry'],
        );
    }
}
