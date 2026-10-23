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

class Inn extends Model implements Bookable
{
    use AssignsPlatformProvider, HasBookings, HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'slug',
        'description',
        'cover_image',
        'location',
        'price',
        'services',
        'availability',
        'rating',
        'status',
    ];

    protected $casts = [
        'price' => 'integer',
        'services' => 'array',
        'availability' => 'boolean',
        'rating' => 'decimal:1',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(InnRoom::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
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
