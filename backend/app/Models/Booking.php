<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Booking extends Model
{
    use HasFactory;

    public const TYPES = ['gastronomic', 'accommodation', 'experience'];

    public const STATUSES = ['pending', 'confirmed', 'cancelled', 'completed'];

    public const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded', 'free'];

    /**
     * Máquina de estados: estado actual => estados alcanzables.
     *
     * `cancelled` y `completed` son terminales (una reserva cancelada no
     * vuelve a "confirmarse"). BookingStatusService valida cada arista
     * contra esta tabla, de modo que un endpoint mal escrito no puede
     * resucitar una reserva cancelada.
     */
    public const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'cancelled' => [],
        'completed' => [],
    ];

    /**
     * Estados que el propio viajero puede solicitar desde la API pública.
     *
     * Confirmar o completar una reserva es una decisión del negocio
     * (backoffice), no del cliente: la API anónima solo permite cancelar.
     */
    public const CUSTOMER_STATUSES = ['cancelled'];

    /** Estados que nunca admiten una transición de vuelta. */
    public function isFinalState(): bool
    {
        return self::TRANSITIONS[$this->status] === [];
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    protected $fillable = [
        'customer_id',
        'booking_code',
        'bookable_type',
        'bookable_id',
        'provider_id',
        'install_id',
        'booking_access_hash',
        'type',
        'start_at',
        'end_at',
        'guests',
        'total_amount',
        'currency',
        'special_requests',
        'status',
        'payment_status',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'guests' => 'integer',
        'total_amount' => 'integer',
    ];

    /**
     * El hash del token de acceso jamás sale serializado. El token en claro
     * se entrega una sola vez (atributo transitorio `plain_access_token`
     * fijado por BookingService) y solo lo expone BookingResource cuando
     * está presente, que es únicamente en la respuesta de creación.
     */
    protected $hidden = [
        'booking_access_hash',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Servicio reservado (Business, Accommodation, Experience, Tour,
     * Restaurant o Inn).
     *
     * Único morph de la reserva: `provider_id` se deriva de aquí en
     * BookingService, así que las notificaciones al proveedor y el
     * ownership del panel cuelgan de esta misma relación.
     */
    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }
}
