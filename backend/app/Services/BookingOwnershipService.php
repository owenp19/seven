<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Http\Request;

/**
 * BookingOwnershipService
 *
 * La v1 es anónima: no hay sesión ni token de usuario, así que cada reserva
 * recibe su propia credencial, el `booking_access_token`, generado por
 * BookingService (48 bytes aleatorios), entregado UNA sola vez en el response
 * de creación y guardado en base solo como SHA-256.
 *
 * Consultar, actualizar o cancelar una reserva por su `booking_code` público
 * exige ese token (`X-Booking-Token`, con respaldo en el body). El
 * `install_id` queda como identificador de alcance para LISTAR las reservas
 * del dispositivo y para el rate limit de comentarios: es una señal, no una
 * credencial, y no abre ninguna reserva concreta.
 *
 * Las filas legacy (creadas antes del acceso por token, con
 * `booking_access_hash` NULL) no tienen propietario verificable: si se
 * permitieran por el código solo, cualquier 3º con el enlace podría leerlas
 * y cancelarlas. Quedan bloqueadas en la API pública (403) y siguen
 * consultables por admin/proveedor vía /admin/bookings. No se borran.
 */
class BookingOwnershipService
{
    /**
     * install_id aportado por la petición, si lo hay.
     *
     * Se acepta en la cabecera `X-Install-Id` (preferida: no contamina el
     * cuerpo ni queda en logs de aplicación) o en el campo `install_id`
     * del cuerpo. Solo sirve para scope de listado, nunca como credencial.
     */
    public function claim(Request $request): ?string
    {
        $claim = $request->header('X-Install-Id') ?? $request->input('install_id');

        if (! is_string($claim)) {
            return null;
        }

        $claim = trim($claim);

        return $claim === '' ? null : $claim;
    }

    /**
     * Token de acceso de la reserva aportado por la petición, si lo hay.
     *
     * Cabecera `X-Booking-Token` (preferida) o campo `booking_access_token`
     * del body. Es la prueba de propiedad de una reserva concreta.
     */
    public function claimToken(Request $request): ?string
    {
        $claim = $request->header('X-Booking-Token') ?? $request->input('booking_access_token');

        if (! is_string($claim)) {
            return null;
        }

        $claim = trim($claim);

        return $claim === '' ? null : $claim;
    }

    /**
     * 403 salvo que la petición aporte el token correcto de la reserva.
     *
     * La comparación es constante en tiempo (hash_equals) y se hace contra
     * el SHA-256 almacenado, del que jamás deriva el token original. Las
     * filas legacy (sin hash) se deniegan siempre en la API pública.
     */
    public function assertOwner(Booking $booking, Request $request): void
    {
        $hash = $booking->booking_access_hash;

        abort_if(
            ! is_string($hash) || $hash === '',
            403,
            'Booking ownership could not be verified.',
        );

        $token = $this->claimToken($request);

        abort_if(
            $token === null,
            403,
            'A booking access token (X-Booking-Token) is required to access this booking.',
        );

        abort_unless(
            hash_equals($hash, hash('sha256', $token)),
            403,
            'You do not have access to this booking.',
        );
    }

    /**
     * install_id obligatorio para listar reservas.
     *
     * Sin este requisito, `GET /bookings` no tendría ámbito y devolvería los
     * booking_code de cualquier teléfono, sirviendo de oráculo de
     * enumeración contra show/update/destroy. El listado NO abre reservas:
     * solo devuelve códigos de ESTE dispositivo.
     */
    public function requireDeviceScope(Request $request): string
    {
        $claim = $this->claim($request);

        abort_if(
            $claim === null,
            403,
            'A device identifier (X-Install-Id) is required to list bookings.',
        );

        return $claim;
    }
}
