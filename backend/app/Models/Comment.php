<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Comment
 *
 * Opinión pública de un viajero sobre un tour, restaurante o posada.
 *
 * No hay login en la app, así que la columna `install_id` (el mismo UUID por
 * dispositivo que usa `bookings`) es la única prueba de autoría. Por eso
 * `install_id` nunca sale en el `CommentResource`: no sirve para nada hacia
 * fuera y filtrarlo por la API convertiría el contador de opiniones en un
 * historial de dispositivos.
 */
class Comment extends Model
{
    use HasFactory;

    public const PUBLISHED = 'published';

    public const PENDING = 'pending';

    public const HIDDEN = 'hidden';

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'install_id',
        'author_name',
        'rating',
        'body',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Cualquier modelo del catálogo puede recibir comentarios. No se ata al
     * contrato `Bookable` porque las Reviews no reservan: solo necesitan
     * exponer la relación.
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::PUBLISHED);
    }

    /**
     * Un comentario concreto de un destino. `commentable_type` guarda la FQCN
     * de la clase (`App\Models\Tour`), no la etiqueta de la API (`tour`).
     */
    public function scopeForTarget(Builder $query, Model $commentable): Builder
    {
        return $query
            ->where('commentable_type', $commentable::class)
            ->where('commentable_id', $commentable->getKey());
    }

    /** Rango de estados que acepta el panel de administración. */
    public static function statuses(): array
    {
        return [self::PUBLISHED, self::PENDING, self::HIDDEN];
    }
}
