<?php

namespace App\Models;

use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'image',
        'button_text',
        'button_url',
        'status',
        'order',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'order' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('start_date')->orWhere('start_date', '<=', today());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', today());
            })
            ->orderBy('order');
    }

    /** Invalidar el caché de catálogo al crear/guardar o borrar. */
    protected static function booted(): void
    {
        static::saved(fn () => CatalogCache::flushBanners());
        static::deleted(fn () => CatalogCache::flushBanners());
    }
}
