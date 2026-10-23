<?php

namespace App\Services\Catalog;

/**
 * CatalogItemDTO
 *
 * Representación normalizada de un servicio del catálogo, con independencia
 * de la vertical (tour, experiencia, posada, alojamiento, restaurante,
 * panadería, negocio). Es lo único que viaja al modelo y al cliente: un
 * contrato estable, sin campos internos (ni `install_id`, ni emails privados,
 * ni relaciones crudas).
 *
 * Reglas de honestidad de datos:
 *  - `price` es el precio unitario REAL; `priceUnit` dice POR QUÉ unidad
 *    (per_person / per_night / per_item). Un precio de restaurante escalar no
 *    existe: si no hay precio fiable, `price` es null y el ítem no entra en
 *    un paquete presupuestado.
 *  - `route` solo se rellena cuando existe una pantalla real en el cliente;
 *    si no hay pantalla, queda null (mejora futura).
 *  - `availability` es 'unknown' salvo que el catálogo ya publique el dato
 *    (p. ej. el flag de una posada): nunca se inventa disponibilidad por fecha.
 */
final class CatalogItemDTO
{
    public const UNIT_PER_PERSON = 'per_person';

    public const UNIT_PER_NIGHT = 'per_night';

    public const UNIT_PER_ITEM = 'per_item';

    /**
     * @param  array<int, string>|null  $route
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $summary = null,
        public readonly ?int $price = null,
        public readonly ?string $priceUnit = null,
        public readonly ?float $rating = null,
        public readonly ?int $reviewCount = null,
        public readonly ?string $location = null,
        public readonly ?string $image = null,
        public readonly ?array $route = null,
        public readonly ?string $provider = null,
        public readonly string $availability = 'unknown',
        public readonly array $extra = [],
    ) {}

    public function hasPrice(): bool
    {
        return is_int($this->price) && $this->price > 0;
    }

    /**
     * Coste estimado para `$people` personas según la unidad de precio.
     *
     *  - per_person : precio x personas (una experiencia, una comida...).
     *  - per_night  : precio x noches (el alojamiento no escala por persona).
     *  - per_item   : precio fijo.
     *
     * Devuelve null si el ítem no tiene precio: un servicio sin precio NO
     * puede entrar en un paquete con presupuesto (§30).
     */
    public function estimatedCost(int $people, int $nights = 1): ?int
    {
        if (! $this->hasPrice()) {
            return null;
        }

        return match ($this->priceUnit) {
            self::UNIT_PER_PERSON => $this->price * max(1, $people),
            self::UNIT_PER_NIGHT => $this->price * max(1, $nights),
            default => $this->price,
        };
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'name' => $this->name,
            'summary' => $this->summary,
            'price' => $this->price,
            'price_unit' => $this->priceUnit,
            'rating' => $this->rating,
            'review_count' => $this->reviewCount,
            'location' => $this->location,
            'image' => $this->image,
            'route' => $this->route,
            'provider' => $this->provider,
            'availability' => $this->availability,
            'extra' => $this->extra,
        ];
    }
}
