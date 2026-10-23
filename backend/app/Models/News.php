<?php

namespace App\Models;

use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Noticia editorial.
 *
 * Sustituye al "montaje" que hacia la pantalla de Noticias con banners y
 * promociones. Es contenido redactado por el equipo, asi que a diferencia del
 * catalogo no tiene `status = approved`: se publica (`published`), se programa
 * (`published_at` en el futuro) o se retira (`draft` / `archived`).
 */
class News extends Model
{
    use HasFactory;

    public const KIND_NEWS = 'news';

    public const KIND_EVENT = 'event';

    public const KIND_TIP = 'tip';

    public const KIND_PROMOTION = 'promotion';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'image',
        'kind',
        'provider_id',
        'link_url',
        'status',
        'published_at',
        'is_featured',
        'order',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'order' => 'integer',
    ];

    /** Etiquetas editoriales admitidas, en el orden en que las muestra la tarjeta. */
    public const KINDS = [
        self::KIND_NEWS,
        self::KIND_EVENT,
        self::KIND_TIP,
        self::KIND_PROMOTION,
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Solo lo que la app puede mostrar ahora mismo.
     *
     * `published_at` nulo cuenta como publicado: el backoffice deja el campo
     * vacio en las notas simples y exigirlo lleno haria fallar el alta de
     * cualquier nota creada desde el panel sin abrir el calendario.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $inner) {
                $inner->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Orden del listado: primero las destacadas y despues lo mas reciente.
     *
     * `id` al final desempata dos notas con la misma fecha y el mismo `order`,
     * que si no devolverian un orden arbitrario entre peticiones y la pantalla
     * saltaria al recargar.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_featured')
            ->orderBy('order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /** Genera el slug a partir del titulo cuando el backoffice no lo envia. */
    protected static function booted(): void
    {
        static::saving(function (self $news) {
            if (blank($news->slug)) {
                $news->slug = str($news->title)->slug()->value();
            }

            if ($news->status === self::STATUS_PUBLISHED && $news->published_at === null) {
                $news->published_at = now();
            }
        });

        static::saved(fn () => CatalogCache::flushNews());
        static::deleted(fn () => CatalogCache::flushNews());
    }
}
