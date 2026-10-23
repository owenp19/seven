<?php

namespace App\Support;

use App\Models\Accommodation;
use App\Models\Business;
use App\Models\Experience;
use App\Models\Inn;
use App\Models\Restaurant;
use App\Models\Tour;

/**
 * BookableType
 *
 * Traduce el modelo polimórfico almacenado en `bookings.bookable_type` a la
 * etiqueta que viaja en la respuesta de la API (`bookable.type`).
 *
 * Centraliza el mapeo porque el cliente lo usa para volver a enrutar: el
 * string tiene que ser el mismo que acepta `POST /bookings` como
 * `bookable_type`, si no un identificador emitido por la API no se puede
 * reenviar en una segunda reserva.
 */
final class BookableType
{
    public const BUSINESS = 'business';

    public const ACCOMMODATION = 'accommodation';

    public const EXPERIENCE = 'experience';

    public const TOUR = 'tour';

    public const RESTAURANT = 'restaurant';

    public const INN = 'inn';

    /**
     * Etiqueta pública del bookable, o null si la relación no está cargada.
     *
     * Se resuelve por `instanceof` y no leyendo el atributo crudo
     * `bookable_type` porque este guarda la FQCN de la clase
     * (`App\Models\Inn`), que no es lo que el cliente espera.
     */
    public static function label(?object $bookable): ?string
    {
        return match (true) {
            $bookable === null => null,
            $bookable instanceof Accommodation => self::ACCOMMODATION,
            $bookable instanceof Experience => self::EXPERIENCE,
            $bookable instanceof Tour => self::TOUR,
            $bookable instanceof Restaurant => self::RESTAURANT,
            $bookable instanceof Inn => self::INN,
            $bookable instanceof Business => self::BUSINESS,
            default => null,
        };
    }

    /** Todas las etiquetas aceptadas por `bookable_type`, para la documentación. */
    public static function all(): array
    {
        return [
            self::BUSINESS,
            self::ACCOMMODATION,
            self::EXPERIENCE,
            self::TOUR,
            self::RESTAURANT,
            self::INN,
        ];
    }
}
