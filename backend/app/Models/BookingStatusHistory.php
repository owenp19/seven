<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BookingStatusHistory
 *
 * Registro inmutable de cada transición de estado de una solicitud
 * (trazabilidad). from_status es null para la creación de la solicitud.
 */
class BookingStatusHistory extends Model
{
    use HasFactory;

    public const CHANGED_BY_OPTIONS = ['admin', 'customer', 'system', 'provider'];

    protected $fillable = [
        'booking_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
