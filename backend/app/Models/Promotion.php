<?php

namespace App\Models;

use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /** Estados admitidos, en el orden del ciclo de vida (pendiente → activa). */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    protected $fillable = [
        'provider_id',
        'title',
        'description',
        'image',
        'discount',
        'price',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'discount' => 'integer',
        'price' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('start_date')->orWhere('start_date', '<=', today());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', today());
            })
            ->orderBy('id');
    }

    /** Invalidar el caché de catálogo al crear/guardar o borrar. */
    protected static function booted(): void
    {
        static::saved(fn () => CatalogCache::flushPromotions());
        static::deleted(fn () => CatalogCache::flushPromotions());
    }
}
