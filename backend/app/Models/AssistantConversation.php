<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AssistantConversation
 *
 * Conversación del asistente con un dispositivo anónimo. Id ULID opaco,
 * propiedad por `install_id` y caducidad por `expires_at`.
 */
class AssistantConversation extends Model
{
    use HasUlids;

    protected $table = 'assistant_conversations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'install_id',
        'expires_at',
        'last_activity_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class, 'conversation_id')->orderBy('id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
