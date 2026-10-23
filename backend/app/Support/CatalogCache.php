<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * CatalogCache
 *
 * Las listas de categorías, banners, promociones y noticias son casi inmutables:
 * se cachean con el TTL config y se invalidan en bloque al escribir su
 * modelo (ver Category::booted, Banner::booted, Promotion::booted,
 * News::booted).
 *
 * La invalidación usa un contador de versión por dominio en vez de borrar
 * claves: cada key lleva la versión embebida, así `flush` es O(1)
 * (Cache::increment) y sirve bien con Redis en producción.
 */
final class CatalogCache
{
    private const PREFIX = 'catalog:';

    /**
     * Versión actual del dominio, embebida en cada key del catálogo.
     *
     * El default es 0, no 1, y es deliberado: `increment()` sobre una clave
     * inexistente escribe 1 (no 2), así que con un default de 1 el primer
     * `flush` tras un arranque en frío dejaría el contador en 1, generaría
     * la misma key `v1` y el flush sería un no-op silencioso: las entradas
     * viejas sobrevivirían el TTL entero. Arrancando en 0, el primer
     * flush mueve v0 -> v1 y sí invalida.
     */
    private static function version(string $domain): int
    {
        return (int) Cache::get(self::PREFIX.$domain.':version', 0);
    }

    private static function key(string $domain, string $suffix): string
    {
        return sprintf('%s%s:%s.v%d', self::PREFIX, $domain, $suffix, self::version($domain));
    }

    private static function ttl(): int
    {
        return (int) config('api.catalog.ttl', 3600);
    }

    public static function categories(string $suffix, callable $resolver): mixed
    {
        return Cache::remember(self::key('categories', $suffix), self::ttl(), $resolver);
    }

    public static function banners(callable $resolver): mixed
    {
        return Cache::remember(self::key('banners', 'index'), self::ttl(), $resolver);
    }

    public static function promotions(callable $resolver): mixed
    {
        return Cache::remember(self::key('promotions', 'index'), self::ttl(), $resolver);
    }

    /**
     * Noticias publicadas, ordenadas.
     *
     * Cachea la colección completa y no una pagina: la pantalla de Noticias no
     * pagina (es un feed corto que cabe en un pull-to-refresh) y una entrada por
     * pagina multiplicaria las claves por el tamano de pagina, que el cliente
     * puede cambiar a voluntad.
     */
    public static function news(callable $resolver): mixed
    {
        return Cache::remember(self::key('news', 'index'), self::ttl(), $resolver);
    }

    public static function flushCategories(): void
    {
        self::bump('categories');
    }

    public static function flushBanners(): void
    {
        self::bump('banners');
    }

    public static function flushPromotions(): void
    {
        self::bump('promotions');
    }

    public static function flushNews(): void
    {
        self::bump('news');
    }

    public static function flushAll(): void
    {
        self::flushCategories();
        self::flushBanners();
        self::flushPromotions();
        self::flushNews();
    }

    /**
     * Avanza el contador de versión de un dominio.
     *
     * Siembra el contador con `add` (no-op si ya existe) antes de
     * `increment`. `increment()` sobre una clave ausente escribe 1 en vez
     * de 2, y devuelve `false` sin hacer nada si el valor no es numérico;
     * sembrar primero garantiza que siempre haya un entero real y que el
     * flush mueva la versión de verdad, en vez de dejar el catálogo viejo
     * sirviéndose hasta que expire su TTL.
     */
    private static function bump(string $domain): void
    {
        $key = self::PREFIX.$domain.':version';

        // El contador vive mucho más que una entrada de catálogo: si expirara
        // y volviera a 0, una key vieja `v0` todavía viva podría volver a
        // leerse y servir contenido retirado.
        Cache::add($key, 0, now()->addDays(7));
        Cache::increment($key);
    }
}
