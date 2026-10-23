<?php

namespace App\Http\Resources\V1;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CommentResource
 *
 * Opinión de un viajero tal y como la ve la app.
 *
 * `install_id` no se expone: es la clave que impide que un dispositivo opine
 * dos veces, y devolverla convertiría el listado en un historial de quién usa
 * la aplicación. El autor es un nombre libre —sin login no hay nada mejor—,
 * así que va tal cual y el frontend lo escapa al pintarlo.
 */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'author_name' => $this->author_name,
            'body' => $this->body,
            'rating' => $this->rating === null ? null : (int) $this->rating,
            // Un comentario recién enviado que quedó en moderación se
            // devuelve con `pending: true` para que la app muestre "en
            // revisión" en lugar de dejarlo desaparecer sin explicación.
            'pending' => $this->status === Comment::PENDING,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
