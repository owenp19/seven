<?php

namespace App\Services\Catalog;

use App\Models\Accommodation;
use App\Models\Bakery;
use App\Models\Business;
use App\Models\Experience;
use App\Models\Inn;
use App\Models\Restaurant;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * CatalogSearchService
 *
 * Única puerta de entrada del asistente al catálogo REAL de SEVEN. Traduce
 * los filtros que propone el modelo (tipo, categoría, precio, ubicación,
 * valoración, personas...) en consultas acotadas y devuelve una lista de
 * CatalogItemDTO normalizados.
 *
 * Principios:
 *  - SOLO LECTURA. No crea, actualiza ni borra nada.
 *  - SOLO catálogo público (`approved()`): nunca expone borradores ni fichas
 *    pendientes a través del asistente.
 *  - Normaliza a un DTO estable para no filtrar columnas internas.
 *  - Acota el número de filas (`limit`) para controlar coste y contexto.
 */
class CatalogSearchService
{
    /** Verticales soportadas por el asistente. */
    public const TYPES = ['tour', 'experience', 'inn', 'accommodation', 'restaurant', 'bakery', 'business'];

    /**
     * Busca en el catálogo.
     *
     * @param  array{
     *     types?: array<int,string>|null,
     *     category?: string|null,
     *     q?: string|null,
     *     location?: string|null,
     *     min_price?: int|null,
     *     max_price?: int|null,
     *     min_rating?: float|null,
     *     people?: int|null,
     *     limit?: int|null
     * }  $filters
     * @return array<int, CatalogItemDTO>
     */
    public function search(array $filters = []): array
    {
        $limit = $this->clampLimit($filters['limit'] ?? config('ai.max_candidates'));
        $types = $this->normalizeTypes($filters['types'] ?? null);

        $items = [];

        foreach ($types as $type) {
            $items = [...$items, ...$this->searchType($type, $filters, $limit)];
        }

        // Orden determinista: mejor valoración y, a igualdad, más reseñas.
        usort($items, function (CatalogItemDTO $a, CatalogItemDTO $b): int {
            $rating = ($b->rating ?? 0) <=> ($a->rating ?? 0);

            return $rating !== 0 ? $rating : (($b->reviewCount ?? 0) <=> ($a->reviewCount ?? 0));
        });

        return array_slice($items, 0, $limit);
    }

    /**
     * Ficha completa de un servicio concreto (para la tool get_service_detail).
     *
     * @param  string  $key  Slug (tour/restaurant/inn) o id numérico (resto).
     */
    public function detail(string $type, string $key): ?CatalogItemDTO
    {
        $type = strtolower(trim($type));

        return match ($type) {
            'tour' => $this->detailBySlug(Tour::class, $key, 'tour'),
            'restaurant' => $this->detailBySlug(Restaurant::class, $key, 'restaurant'),
            'inn' => $this->detailBySlug(Inn::class, $key, 'inn'),
            'experience' => $this->detailById(Experience::class, $key, 'experience'),
            'accommodation' => $this->detailById(Accommodation::class, $key, 'accommodation'),
            'business' => $this->detailById(Business::class, $key, 'business'),
            'bakery' => $this->detailBySlug(Bakery::class, $key, 'bakery'),
            default => null,
        };
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function detailBySlug(string $model, string $slug, string $type): ?CatalogItemDTO
    {
        $record = $model::approved()->where('slug', $slug)->first();

        return $record ? $this->map($type, $record) : null;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function detailById(string $model, string $id, string $type): ?CatalogItemDTO
    {
        if (! ctype_digit($id)) {
            return null;
        }

        $record = $model::approved()->find((int) $id);

        return $record ? $this->map($type, $record) : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, CatalogItemDTO>
     */
    private function searchType(string $type, array $filters, int $limit): array
    {
        return match ($type) {
            'tour' => $this->searchTour($filters, $limit),
            'experience' => $this->searchExperience($filters, $limit),
            'inn' => $this->searchInn($filters, $limit),
            'accommodation' => $this->searchAccommodation($filters, $limit),
            'restaurant' => $this->searchRestaurant($filters, $limit),
            'bakery' => $this->searchBakery($filters, $limit),
            'business' => $this->searchBusiness($filters, $limit),
            default => [],
        };
    }

    private function searchTour(array $f, int $limit): array
    {
        $query = Tour::approved()->with('provider:id,business_name');
        $this->applyText($query, $f['q'] ?? null, ['name', 'description', 'location', 'category']);
        $this->applyScalarCategory($query, $f['category'] ?? null);
        $this->applyPriceRange($query, 'price', $f);
        $this->applyMinRating($query, 'rating', $f['min_rating'] ?? null);
        $this->applyLocation($query, ['location'], $f['location'] ?? null);

        return $query->orderByDesc('rating')->orderByDesc('reviews')->limit($limit)->get()
            ->map(fn (Tour $t) => $this->map('tour', $t))->all();
    }

    private function searchExperience(array $f, int $limit): array
    {
        $query = Experience::approved()->with('provider:id,business_name');
        $this->applyText($query, $f['q'] ?? null, ['name', 'description']);
        $this->applyRelationCategory($query, $f['category'] ?? null);
        $this->applyPriceRange($query, 'price_per_person', $f);
        $this->applyMinRating($query, 'rating_avg', $f['min_rating'] ?? null);
        $this->applyLocation($query, ['address'], $f['location'] ?? null);
        $this->applyPeople($query, $f['people'] ?? null);

        return $query->orderByDesc('rating_avg')->orderByDesc('rating_count')->limit($limit)->get()
            ->map(fn (Experience $e) => $this->map('experience', $e))->all();
    }

    private function searchInn(array $f, int $limit): array
    {
        $query = Inn::approved()->with('provider:id,business_name');
        $this->applyText($query, $f['q'] ?? null, ['name', 'description', 'location']);
        $this->applyPriceRange($query, 'price', $f);
        $this->applyMinRating($query, 'rating', $f['min_rating'] ?? null);
        $this->applyLocation($query, ['location'], $f['location'] ?? null);

        return $query->orderByDesc('rating')->limit($limit)->get()
            ->map(fn (Inn $i) => $this->map('inn', $i))->all();
    }

    private function searchAccommodation(array $f, int $limit): array
    {
        $query = Accommodation::approved()->with('provider:id,business_name');
        $this->applyText($query, $f['q'] ?? null, ['name', 'description', 'address']);
        $this->applyPriceRange($query, 'price_per_night', $f);
        $this->applyMinRating($query, 'rating_avg', $f['min_rating'] ?? null);
        $this->applyLocation($query, ['address'], $f['location'] ?? null);
        $this->applyPeople($query, $f['people'] ?? null);

        return $query->orderByDesc('rating_avg')->orderByDesc('rating_count')->limit($limit)->get()
            ->map(fn (Accommodation $a) => $this->map('accommodation', $a))->all();
    }

    private function searchRestaurant(array $f, int $limit): array
    {
        $query = Restaurant::approved()->with('provider:id,business_name');
        $this->applyText($query, $f['q'] ?? null, ['name', 'description', 'address', 'category']);
        $this->applyScalarCategory($query, $f['category'] ?? null);
        $this->applyMinRating($query, 'rating', $f['min_rating'] ?? null);
        $this->applyLocation($query, ['address'], $f['location'] ?? null);

        return $query->orderByDesc('rating')->orderByDesc('reviews')->limit($limit)->get()
            ->map(fn (Restaurant $r) => $this->map('restaurant', $r))->all();
    }

    private function searchBakery(array $f, int $limit): array
    {
        $query = Bakery::approved()->with('provider:id,business_name');
        $this->applyText($query, $f['q'] ?? null, ['name', 'description', 'address', 'category']);
        $this->applyScalarCategory($query, $f['category'] ?? null);
        $this->applyMinRating($query, 'rating', $f['min_rating'] ?? null);
        $this->applyLocation($query, ['address'], $f['location'] ?? null);

        return $query->orderByDesc('rating')->orderByDesc('reviews')->limit($limit)->get()
            ->map(fn (Bakery $b) => $this->map('bakery', $b))->all();
    }

    private function searchBusiness(array $f, int $limit): array
    {
        $query = Business::approved()->with('provider:id,business_name');
        $this->applyText($query, $f['q'] ?? null, ['name', 'description']);
        $this->applyRelationCategory($query, $f['category'] ?? null);
        $this->applyMinRating($query, 'rating_avg', $f['min_rating'] ?? null);
        $this->applyLocation($query, ['address'], $f['location'] ?? null);

        return $query->orderByDesc('rating_avg')->orderByDesc('rating_count')->limit($limit)->get()
            ->map(fn (Business $b) => $this->map('business', $b))->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Mapeo modelo -> DTO
    |--------------------------------------------------------------------------
    */

    private function map(string $type, Model $record): CatalogItemDTO
    {
        return match ($type) {
            'tour' => new CatalogItemDTO(
                type: 'tour',
                id: (string) $record->id,
                name: (string) $record->name,
                summary: $this->summary($record->subtitle ?: $record->description),
                price: (int) $record->price,
                priceUnit: CatalogItemDTO::UNIT_PER_PERSON,
                rating: (float) $record->rating,
                reviewCount: (int) $record->reviews,
                location: $record->location,
                image: mediaUrl($record->cover_image),
                route: ['/tour', (string) $record->slug],
                provider: $record->provider?->business_name,
                availability: 'unknown',
            ),
            'experience' => new CatalogItemDTO(
                type: 'experience',
                id: (string) $record->id,
                name: (string) $record->name,
                summary: $this->summary($record->description),
                price: (int) $record->price_per_person,
                priceUnit: CatalogItemDTO::UNIT_PER_PERSON,
                rating: (float) ($record->rating_avg ?? 0),
                reviewCount: (int) ($record->rating_count ?? 0),
                location: $record->address,
                image: $this->firstImage($record->images),
                route: null,
                provider: $record->provider?->business_name,
                availability: 'unknown',
                extra: ['duration_minutes' => $record->duration_minutes, 'currency' => $record->currency],
            ),
            'inn' => new CatalogItemDTO(
                type: 'inn',
                id: (string) $record->id,
                name: (string) $record->name,
                summary: $this->summary($record->description),
                price: (int) $record->price,
                priceUnit: CatalogItemDTO::UNIT_PER_NIGHT,
                rating: (float) $record->rating,
                reviewCount: null,
                location: $record->location,
                image: mediaUrl($record->cover_image),
                route: ['/inn', (string) $record->slug],
                provider: $record->provider?->business_name,
                availability: $record->availability ? 'available' : 'unavailable',
            ),
            'accommodation' => new CatalogItemDTO(
                type: 'accommodation',
                id: (string) $record->id,
                name: (string) $record->name,
                summary: $this->summary($record->description),
                price: (int) $record->price_per_night,
                priceUnit: CatalogItemDTO::UNIT_PER_NIGHT,
                rating: (float) ($record->rating_avg ?? 0),
                reviewCount: (int) ($record->rating_count ?? 0),
                location: $record->address,
                image: $this->firstImage($record->images),
                route: null,
                provider: $record->provider?->business_name,
                availability: 'unknown',
                extra: ['capacity' => $record->capacity, 'currency' => $record->currency],
            ),
            'restaurant' => new CatalogItemDTO(
                type: 'restaurant',
                id: (string) $record->id,
                name: (string) $record->name,
                summary: $this->summary($record->description),
                price: null,
                priceUnit: null,
                rating: (float) $record->rating,
                reviewCount: (int) $record->reviews,
                location: $record->address,
                image: mediaUrl($record->cover_image ?: $record->logo),
                route: ['/restaurant', (string) $record->slug],
                provider: $record->provider?->business_name,
                availability: 'unknown',
                extra: ['category' => $record->category, 'price_range' => $record->price_range],
            ),
            'bakery' => new CatalogItemDTO(
                type: 'bakery',
                id: (string) $record->id,
                name: (string) $record->name,
                summary: $this->summary($record->description),
                price: null,
                priceUnit: null,
                rating: (float) $record->rating,
                reviewCount: (int) $record->reviews,
                location: $record->address,
                image: mediaUrl($record->cover_image ?: $record->logo),
                route: null,
                provider: $record->provider?->business_name,
                availability: 'unknown',
                extra: ['category' => $record->category, 'price_range' => $record->price_range],
            ),
            'business' => new CatalogItemDTO(
                type: 'business',
                id: (string) $record->id,
                name: (string) $record->name,
                summary: $this->summary($record->description),
                price: null,
                priceUnit: null,
                rating: (float) ($record->rating_avg ?? 0),
                reviewCount: (int) ($record->rating_count ?? 0),
                location: $record->address,
                image: $this->firstImage($record->images),
                route: ['/business', (string) $record->id],
                provider: $record->provider?->business_name,
                availability: 'unknown',
                extra: ['category' => $record->category?->name, 'is_verified' => (bool) $record->is_verified],
            ),
            default => throw new \InvalidArgumentException("Unknown catalog type [{$type}]."),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers de filtrado
    |--------------------------------------------------------------------------
    */

    private function applyText(Builder $query, ?string $q, array $columns): void
    {
        $q = $this->clean($q);

        if ($q === null) {
            return;
        }

        $query->where(function (Builder $inner) use ($q, $columns): void {
            foreach ($columns as $column) {
                $inner->orWhere($column, 'like', '%'.$this->escapeLike($q).'%');
            }
        });
    }

    private function applyScalarCategory(Builder $query, ?string $category): void
    {
        $category = $this->clean($category);

        if ($category !== null) {
            $query->where('category', $category);
        }
    }

    private function applyRelationCategory(Builder $query, ?string $category): void
    {
        $category = $this->clean($category);

        if ($category !== null) {
            $query->whereHas('category', fn (Builder $relation) => $relation->where('slug', $category));
        }
    }

    private function applyLocation(Builder $query, array $columns, ?string $location): void
    {
        $location = $this->clean($location);

        if ($location === null) {
            return;
        }

        $query->where(function (Builder $inner) use ($columns, $location): void {
            foreach ($columns as $column) {
                $inner->orWhere($column, 'like', '%'.$this->escapeLike($location).'%');
            }
        });
    }

    private function applyPriceRange(Builder $query, string $column, array $filters): void
    {
        $min = $this->positiveInt($filters['min_price'] ?? null);
        $max = $this->positiveInt($filters['max_price'] ?? null);

        if ($min !== null) {
            $query->where($column, '>=', $min);
        }

        if ($max !== null) {
            $query->where($column, '<=', $max);
        }
    }

    private function applyMinRating(Builder $query, string $column, mixed $minRating): void
    {
        if (is_numeric($minRating)) {
            $query->where($column, '>=', (float) $minRating);
        }
    }

    private function applyPeople(Builder $query, mixed $people): void
    {
        $people = $this->positiveInt($people);

        if ($people !== null) {
            $query->where('max_capacity', '>=', $people);
        }
    }

    private function summary(?string $text): ?string
    {
        $text = $this->clean($text);

        return $text === null ? null : Str::limit($text, 160);
    }

    private function firstImage(mixed $images): ?string
    {
        $urls = mediaUrls($images);

        return $urls[0] ?? null;
    }

    private function clean(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /**
     * @param  array<int, string>|null  $types
     * @return array<int, string>
     */
    private function normalizeTypes(?array $types): array
    {
        if (empty($types)) {
            return self::TYPES;
        }

        $types = array_values(array_intersect(
            array_map(fn ($t) => strtolower(trim((string) $t)), $types),
            self::TYPES,
        ));

        return $types === [] ? self::TYPES : $types;
    }

    private function clampLimit(mixed $limit): int
    {
        $limit = is_numeric($limit) ? (int) $limit : (int) config('ai.max_candidates');

        return max(1, min($limit, 30));
    }
}
