<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourRecommendation extends Model
{
    protected $fillable = ['tour_id', 'icon', 'name'];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
