<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourImage extends Model
{
    protected $fillable = ['tour_id', 'image', 'order'];

    protected $casts = ['order' => 'integer'];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
