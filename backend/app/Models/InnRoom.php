<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InnRoom extends Model
{
    protected $fillable = [
        'inn_id',
        'name',
        'description',
        'price',
        'image',
        'capacity',
    ];

    protected $casts = [
        'price' => 'integer',
        'capacity' => 'integer',
    ];

    public function inn(): BelongsTo
    {
        return $this->belongsTo(Inn::class);
    }
}
