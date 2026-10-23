<?php

namespace App\Models;

use App\Models\Concerns\AssignsPlatformProvider;
use App\Models\Concerns\HasBookings;
use App\Models\Contracts\Bookable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Business extends Model implements Bookable
{
    use AssignsPlatformProvider, HasBookings, HasFactory;

    protected $fillable = [
        'provider_id',
        'category_id',
        'name',
        'slug',
        'description',
        'address',
        'latitude',
        'longitude',
        'phone',
        'whatsapp',
        'email',
        'images',
        'opening_hours',
        'tags',
        'features',
        'is_verified',
        'rating_avg',
        'rating_count',
        'status',
    ];

    protected $casts = [
        'images' => 'array',
        'opening_hours' => 'array',
        'tags' => 'array',
        'features' => 'array',
        'is_verified' => 'boolean',
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
