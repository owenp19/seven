<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Provider extends Model
{
    use HasFactory;

    /**
     * Proveedor de plataforma: absorbe los servicios heredados sin cuenta
     * de proveedor y es el fallback de provider_id en el backoffice global.
     * Misma clave que usa la migración 2026_10_08_000200 (business_name).
     */
    public const PLATFORM_NAME = 'SEVEN (gestión directa)';

    protected $fillable = [
        'user_id',
        'business_name',
        'owner_name',
        'email',
        'phone',
        'address',
        'logo',
        'type',
        'status',
        'verification_status',
    ];

    /**
     * @return list<class-string<Model>>
     */
    public static function serviceModels(): array
    {
        return [
            Tour::class,
            Restaurant::class,
            Inn::class,
            Bakery::class,
            Business::class,
            Accommodation::class,
            Experience::class,
        ];
    }

    /** Fila de plataforma (idempotente por business_name). */
    public static function platform(): static
    {
        return static::firstOrCreate(
            ['business_name' => static::PLATFORM_NAME],
            [
                'type' => 'tour_operator',
                'status' => 'approved',
                'verification_status' => 'verified',
            ],
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }

    public function inns(): HasMany
    {
        return $this->hasMany(Inn::class);
    }

    public function bakeries(): HasMany
    {
        return $this->hasMany(Bakery::class);
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function accommodations(): HasMany
    {
        return $this->hasMany(Accommodation::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
