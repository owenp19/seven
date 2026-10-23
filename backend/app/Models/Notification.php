<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'title',
        'message',
        'type',
        'read',
    ];

    protected $casts = ['read' => 'boolean'];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
