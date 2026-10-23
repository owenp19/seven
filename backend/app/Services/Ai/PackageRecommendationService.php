<?php

namespace App\Services\Ai;

use App\Services\Catalog\CatalogItemDTO;
use App\Services\Catalog\CatalogSearchService;
use Illuminate\Support\Str;

/**
 * PackageRecommendationService
 *
 * Toda la aritmética del asistente vive aquí, en PHP determinista: el modelo
 * NUNCA suma ni multiplica. Este servicio:
 *  - filtra los servicios que caben en un presupuesto para N personas,
 *  - arma combinaciones (paquetes) que NO superan el presupuesto,
 *  - ordena por una puntuación transparente (valoración + confianza + afinidad
 *    con la preferencia + ajuste al presupuesto), no "lo más barato".
 *
 * Reglas de honestidad (§30):
 *  - Un ítem sin precio NO participa en paquetes presupuestados.
 *  - El coste es precio x unidad (per_person x personas, per_night x noches).
 *  - Nunca se propone un paquete cuyo total supere el presupuesto.
 */
class PackageRecommendationService
{
    /** Máximo de candidatos por vertical que entran en las combinaciones. */
    private const TOP_PER_TYPE = 4;

    /** Tope de combinaciones evaluadas (controla coste; determinista). */
    private const MAX_COMBINATIONS = 500;

    public function __construct(
        private readonly CatalogSearchService $catalog,
    ) {}

    /**
     * Servicios individuales que caben en el presupuesto, ordenados por
     * puntuación. Es la respuesta directa a "¿qué puedo hacer con $X?".
     *
     * @param  array<int, string>  $types
     * @return array<int, CatalogItemDTO>
     */
    public function findByBudget(int $budget, int $people, array $types, ?string $preferences = null): array
    {
        $people = max(1, $people);
        $candidates = $this->candidates($types, $people, $preferences);

        $affordable = array_filter(
            $candidates,
            fn (CatalogItemDTO $item): bool => ($item->estimatedCost($people) ?? PHP_INT_MAX) <= $budget,
        );

        usort($affordable, fn (CatalogItemDTO $a, CatalogItemDTO $b): int => $this->compare($a, $b, $budget, $people, $preferences));

        return array_slice(array_values($affordable), 0, (int) config('ai.max_recommendations', 3));
    }

    /**
     * Paquetes (combinaciones de servicios) que caben en el presupuesto.
     *
     * @param  array<int, string>  $types
     * @return array<int, array{items: array<int, CatalogItemDTO>, total: int, remaining: int}>
     */
    public function buildPackage(int $budget, int $people, array $types, ?string $preferences = null): array
    {
        $people = max(1, $people);
        $candidates = $this->candidates($types, $people, $preferences);
        $candidates = array_filter($candidates, fn (CatalogItemDTO $item): bool => $item->hasPrice());

        if ($candidates === []) {
            return [];
        }

        // Agrupa por vertical y conserva los mejores de cada una.
        $byType = [];
        foreach ($candidates as $item) {
            $byType[$item->type][] = $item;
        }

        $groups = [];
        foreach ($byType as $type => $items) {
            usort($items, fn (CatalogItemDTO $a, CatalogItemDTO $b): int => $this->compare($a, $b, $budget, $people, $preferences));
            $groups[] = array_slice($items, 0, self::TOP_PER_TYPE);
        }

        if ($groups === []) {
            return [];
        }

        $packages = [];
        $evaluated = 0;

        // Producto cartesiano acotado: un ítem por vertical.
        $this->combine($groups, 0, [], function (array $combo) use (&$packages, &$evaluated, $budget, $people): void {
            if (++$evaluated > self::MAX_COMBINATIONS) {
                return;
            }

            $total = 0;
            foreach ($combo as $item) {
                $total += $item->estimatedCost($people) ?? PHP_INT_MAX;
            }

            if ($total > $budget) {
                return;
            }

            usort($combo, fn (CatalogItemDTO $a, CatalogItemDTO $b): int => strcmp($a->type, $b->type));

            $packages[] = [
                'items' => array_values($combo),
                'total' => $total,
                'remaining' => $budget - $total,
            ];
        });

        if ($packages === []) {
            // Sin combinaciones: cae al mejor ítem individual que SÍ quepa.
            $single = $this->findByBudget($budget, $people, $types, $preferences)[0] ?? null;

            if ($single === null) {
                return [];
            }

            $cost = (int) $single->estimatedCost($people);

            return [[
                'items' => [$single],
                'total' => $cost,
                'remaining' => $budget - $cost,
            ]];
        }

        usort($packages, function (array $a, array $b) use ($budget, $people, $preferences): int {
            // Más servicios primero (aprovecha mejor el presupuesto sin pasarse),
            // luego mejor puntuación agregada, luego menos presupuesto sin usar.
            $count = count($b['items']) <=> count($a['items']);
            if ($count !== 0) {
                return $count;
            }

            $scoreA = array_sum(array_map(fn (CatalogItemDTO $i): float => $this->score($i, $budget, $people, $preferences), $a['items']));
            $scoreB = array_sum(array_map(fn (CatalogItemDTO $i): float => $this->score($i, $budget, $people, $preferences), $b['items']));

            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }

            return $a['remaining'] <=> $b['remaining'];
        });

        // Deduplica paquetes idénticos (mismo conjunto de ids).
        $seen = [];
        $unique = [];
        foreach ($packages as $package) {
            $signature = implode('|', array_map(fn (CatalogItemDTO $i) => $i->type.':'.$i->id, $package['items']));
            if (isset($seen[$signature])) {
                continue;
            }
            $seen[$signature] = true;
            $unique[] = $package;
        }

        return array_slice($unique, 0, (int) config('ai.max_recommendations', 3));
    }

    /**
     * @param  array<int, string>  $types
     * @return array<int, CatalogItemDTO>
     */
    private function candidates(array $types, int $people, ?string $preferences): array
    {
        return $this->catalog->search([
            'types' => $types ?: null,
            'people' => $people,
            'limit' => 20,
        ]);
    }

    /**
     * Comparador determinista por puntuación descendente (con desempate estable).
     */
    private function compare(CatalogItemDTO $a, CatalogItemDTO $b, int $budget, int $people, ?string $preferences): int
    {
        $score = $this->score($b, $budget, $people, $preferences) <=> $this->score($a, $budget, $people, $preferences);

        return $score !== 0 ? $score : strcmp($a->type.$a->id, $b->type.$b->id);
    }

    /**
     * Puntuación transparente de un ítem para un presupuesto/personas.
     */
    private function score(CatalogItemDTO $item, int $budget, int $people, ?string $preferences): float
    {
        $rating = $item->rating ?? 0.0;
        $reviewConfidence = min($item->reviewCount ?? 0, 50) / 50 * 5;
        $preference = $this->preferenceMatches($item, $preferences) * 6;
        $availability = $item->availability === 'available' ? 2.0 : ($item->availability === 'unavailable' ? -4.0 : 0.0);

        $cost = $item->estimatedCost($people) ?? $budget;
        $unusedRatio = max(0.0, 1 - ($cost / max(1, $budget)));

        return $rating * 10 + $reviewConfidence + $preference + $availability - $unusedRatio * 3;
    }

    /**
     * Coincidencias de la preferencia (texto libre) contra el ítem. Heurística
     * determinista sobre datos REALES: solo cuenta términos presentes en el
     * nombre/resumen; nunca inventa atributos.
     */
    private function preferenceMatches(CatalogItemDTO $item, ?string $preferences): int
    {
        $preferences = $this->normalize($preferences);

        if ($preferences === '') {
            return 0;
        }

        $haystack = $this->normalize($item->name.' '.($item->summary ?? '').' '.implode(' ', $item->extra ?? []));

        $matches = 0;
        foreach (array_unique(preg_split('/[^a-z0-9]+/', $preferences) ?: []) as $term) {
            if (strlen($term) >= 4 && str_contains($haystack, $term)) {
                $matches++;
            }
        }

        return $matches;
    }

    private function normalize(?string $value): string
    {
        return Str::lower(Str::ascii(trim((string) $value)));
    }

    /**
     * Producto cartesiano recursivo y acotado.
     *
     * @param  array<int, array<int, CatalogItemDTO>>  $groups
     * @param  array<int, CatalogItemDTO>  $current
     */
    private function combine(array $groups, int $index, array $current, callable $callback): void
    {
        if ($index >= count($groups)) {
            $callback($current);

            return;
        }

        foreach ($groups[$index] as $item) {
            $this->combine($groups, $index + 1, [...$current, $item], $callback);
        }
    }
}
