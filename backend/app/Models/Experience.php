<?php

namespace App\Models;

use App\Models\Concerns\AssignsPlatformProvider;
use App\Models\Concerns\HasBookings;
use App\Models\Contracts\Bookable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model implements Bookable
{
    use AssignsPlatformProvider, HasBookings, HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'slug',
        'description',
        'category_id',
        'duration_minutes',
        'price_per_person',
        'currency',
        'max_capacity',
        'address',
        'latitude',
        'longitude',
        'includes',
        'images',
        'user_id',
        'rating_avg',
        'rating_count',
        'status',
    ];

    protected $casts = [
        'includes' => 'array',
        'images' => 'array',
        'duration_minutes' => 'integer',
        'price_per_person' => 'integer',
        'max_capacity' => 'integer',
        'rating_avg' => 'decimal:1',
        'rating_count' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
