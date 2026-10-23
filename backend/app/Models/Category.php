<?php

namespace App\Models;

use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * Estados de la categoría. `status` es la única fuente de verdad; las
     * columnas legacy `is_active` y `sort_order` se derivan de ella.
     */
    public const ACTIVE = 'active';

    public const INACTIVE = 'inactive';

    public const STATUSES = [self::ACTIVE, self::INACTIVE];

    protected $fillable = [
        'slug',
        'name',
        'icon',
        'image',
        'description',
        'sort_order',
        'is_active',
        'order',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Categoría visible en el catálogo público.
     *
     * Filtra por `status` y no por `is_active`: hasta ahora las dos rutas
     * leían columnas distintas, así que un PATCH {"status":"inactive"}
     * respondía 200 y la categoría seguía apareciendo en el listado.
     *
     * El NULL se acepta como activa a propósito: la columna tiene default
     * 'active' pero el panel podía fijarla a null, y una categoría sin
     * estado no debe desaparecer del catálogo por sorpresa. El OR va
     * agrupado para no arrastrar a los demás filtros del builder.
     */
    public function scopeActive($query)
    {
        return $query->where(fn ($inner) => $inner
            ->where('status', self::ACTIVE)
            ->orWhereNull('status'));
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    /**
     * Negocio vigente al retirar la categoría.
     *
     * Las reservas cuelgan del negocio (`bookings.bookable_type` +
     * `bookable_id`), no de la categoría, así que desactivar una categoría
     * deja intactas las reservas ya creadas. Se expone para que el seeder
     * pueda avisar de lo que se va a ocultar en vez de borrarlo.
     */
    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

    /**
     * Colapsa el par legacy (`is_active`/`sort_order`) en el canónico
     * (`status`/`order`).
     *
     * El cliente todavía lee `sort_order` de la respuesta y quedan
     * consumidores legacy sobre `is_active`, así que las columnas viejas no
     * se pueden borrar. Pero mantener dos verdades era justo lo que hacía
     * que un PATCH {"status":"inactive"} respondiera 200 sin ocultar nada.
     *
     * En cada guardado gana la columna canónica del par, y si quien llama
     * solo tocó la legacy, esa se adopta como canónica. Así las dos
     * columnas quedan siempre de acuerdo dentro del mismo INSERT/UPDATE y
     * da igual cuál se escriba.
     */
    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            self::syncVisibility($category);
            self::syncOrdering($category);
        });

        static::saved(fn () => CatalogCache::flushCategories());
        static::deleted(fn () => CatalogCache::flushCategories());
    }

    /** `status` manda; un `is_active` explícito sin `status` se adopta. */
    private static function syncVisibility(self $category): void
    {
        $canonicalTouched = $category->isDirty('status');
        $legacyTouched = $category->isDirty('is_active');

        if (! $canonicalTouched && $legacyTouched) {
            $category->status = $category->is_active ? self::ACTIVE : self::INACTIVE;
        }

        // Normaliza el NULL heredado a `active` para que la columna nunca
        // vuelva a quedar sin estado (y `isVisible()` no tenga que
        // adivinarlo). Coincide con lo que ya asumía scopeActive().
        if ($category->status === null) {
            $category->status = self::ACTIVE;
        }

        $category->is_active = $category->isVisible();
    }

    /** `order` manda; un `sort_order` explícito sin `order` se adopta. */
    private static function syncOrdering(self $category): void
    {
        if (! $category->isDirty('order') && $category->isDirty('sort_order')) {
            $category->order = (int) $category->sort_order;
        }

        $category->sort_order = (int) ($category->order ?? 0);
    }

    /** Visible en el catálogo público. */
    public function isVisible(): bool
    {
        return $this->status === null || $this->status === self::ACTIVE;
    }
}
