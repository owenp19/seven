<?php

namespace App\Support;

/**
 * WhatsApp
 *
 * Genera deep-links wa.me para el flujo "solicitar disponibilidad":
 * tras crear la solicitud el frontend abre WhatsApp con el mensaje
 * prellenado que incluye el código de referencia (ver BookingResource).
 */
final class WhatsApp
{
    public static function link(?string $phone, ?string $message = null): ?string
    {
        $digits = self::digits($phone);

        if ($digits === '') {
            return null;
        }

        $country = (string) config('api.whatsapp.default_country_code', '57');

        // Solo se antepone el código de país cuando el número parece local
        // (móvil colombiano "300..." → "57300..."); si ya trae prefijo o es
        // internacional (más de 10 dígitos) se usa tal cual.
        if (! str_starts_with($digits, $country) && strlen($digits) <= 10) {
            $digits = $country.$digits;
        }

        $url = 'https://wa.me/'.$digits;

        if (is_string($message) && $message !== '') {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }

    /** Solo dígitos del teléfono (+57 300 123-4567 → 573001234567). */
    public static function digits(?string $phone): string
    {
        if (! is_string($phone) || $phone === '') {
            return '';
        }

        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
