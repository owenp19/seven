<?php

namespace App\Models;

use App\Models\Concerns\AssignsPlatformProvider;
use App\Models\Concerns\HasBookings;
use App\Models\Contracts\Bookable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Tour extends Model implements Bookable
{
    use AssignsPlatformProvider, HasBookings, HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'slug',
        'subtitle',
        'description',
        'category',
        'location',
        'duration',
        'price',
        'rating',
        'reviews',
        'cover_image',
        'status',
    ];

    protected $casts = [
        'price' => 'integer',
        'rating' => 'decimal:1',
        'reviews' => 'integer',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(TourImage::class)->orderBy('order');
    }

    public function includes(): HasMany
    {
        return $this->hasMany(TourInclude::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(TourRecommendation::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
