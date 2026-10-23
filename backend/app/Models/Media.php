<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    protected $fillable = [
        'provider_id',
        'model_type',
        'model_id',
        'file_path',
        'type',
        'order',
    ];

    protected $casts = ['order' => 'integer'];

    /** Accepts both morphMany targets and plain polymorphic owner. */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /** Absolute URL for the media file (public storage). */
    public function getUrlAttribute(): string
    {
        return mediaUrl($this->file_path);
    }
}
