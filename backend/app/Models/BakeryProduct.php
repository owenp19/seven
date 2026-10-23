<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BakeryProduct extends Model
{
    protected $fillable = [
        'bakery_id',
        'name',
        'description',
        'price',
        'image',
        'category',
        'status',
    ];

    protected $casts = ['price' => 'integer'];

    public function bakery(): BelongsTo
    {
        return $this->belongsTo(Bakery::class);
    }
}
