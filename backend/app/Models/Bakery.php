<?php

namespace App\Models;

use App\Models\Concerns\AssignsPlatformProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Bakery extends Model
{
    use AssignsPlatformProvider, HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'slug',
        'logo',
        'cover_image',
        'description',
        'address',
        'phone',
        'category',
        'rating',
        'reviews',
        'opening_hours',
        'price_range',
        'latitude',
        'longitude',
        'status',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'reviews' => 'integer',
        'opening_hours' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(BakeryProduct::class)->where('status', 'active');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
