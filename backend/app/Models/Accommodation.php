<?php

namespace App\Models;

use App\Models\Concerns\AssignsPlatformProvider;
use App\Models\Concerns\HasBookings;
use App\Models\Contracts\Bookable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Accommodation extends Model implements Bookable
{
    use AssignsPlatformProvider, HasBookings, HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'slug',
        'description',
        'capacity',
        'price_per_night',
        'currency',
        'address',
        'latitude',
        'longitude',
        'images',
        'included_services',
        'user_id',
        'rating_avg',
        'rating_count',
        'status',
    ];

    protected $casts = [
        'images' => 'array',
        'included_services' => 'array',
        'capacity' => 'integer',
        'price_per_night' => 'integer',
        'rating_avg' => 'decimal:1',
        'rating_count' => 'integer',
    ];

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
