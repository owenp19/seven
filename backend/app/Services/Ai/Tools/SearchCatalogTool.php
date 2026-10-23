<?php

namespace App\Services\Ai\Tools;

use App\Contracts\Ai\AssistantTool;
use App\Services\Ai\Dto\ToolDefinition;
use App\Services\Catalog\CatalogSearchService;

/**
 * search_catalog
 *
 * Búsqueda libre en el catálogo real de SEVEN. Devuelve servicios que
 * satisfacen los filtros (vertical, categoría, precio, ubicación, valoración,
 * personas). Es de SOLO LECTURA y solo ve fichas aprobadas.
 */
class SearchCatalogTool implements AssistantTool
{
    public function __construct(
        private readonly CatalogSearchService $catalog,
    ) {}

    public function definition(): ToolDefinition
    {
        return new ToolDefinition(
            name: 'search_catalog',
            description: 'Busca servicios reales de SEVEN por tipo, categoría, texto, ubicación, precio, valoración o número de personas. Úsala siempre que el usuario pida recomendaciones o quiera descubrir opciones. Nunca inventes servicios que no devuelva esta herramienta.',
            parameters: [
                'type' => 'object',
                'properties' => [
                    'types' => [
                        'type' => 'array',
                        'description' => 'Verticales a buscar.',
                        'items' => ['type' => 'string', 'enum' => CatalogSearchService::TYPES],
                    ],
                    'q' => ['type' => 'string', 'description' => 'Texto libre (nombre, descripción, lugar).'],
                    'category' => ['type' => 'string', 'description' => 'Categoría/slug del servicio.'],
                    'location' => ['type' => 'string', 'description' => 'Zona o dirección.'],
                    'min_price' => ['type' => 'integer', 'description' => 'Precio unitario mínimo en COP.'],
                    'max_price' => ['type' => 'integer', 'description' => 'Precio unitario máximo en COP.'],
                    'min_rating' => ['type' => 'number', 'description' => 'Valoración mínima (0-5).'],
                    'people' => ['type' => 'integer', 'description' => 'Número de personas.'],
                    'limit' => ['type' => 'integer', 'description' => 'Máximo de resultados.'],
                ],
                'required' => [],
            ],
        );
    }

    public function handle(array $arguments): array
    {
        $items = $this->catalog->search([
            'types' => $this->arrayArg($arguments['types'] ?? null),
            'q' => $this->stringArg($arguments['q'] ?? null),
            'category' => $this->stringArg($arguments['category'] ?? null),
            'location' => $this->stringArg($arguments['location'] ?? null),
            'min_price' => $this->intArg($arguments['min_price'] ?? null),
            'max_price' => $this->intArg($arguments['max_price'] ?? null),
            'min_rating' => $this->floatArg($arguments['min_rating'] ?? null),
            'people' => $this->intArg($arguments['people'] ?? null),
            'limit' => $this->intArg($arguments['limit'] ?? null),
        ]);

        return [
            'count' => count($items),
            'items' => array_map(fn ($item) => $item->toArray(), $items),
        ];
    }

    private function arrayArg(mixed $value): ?array
    {
        return is_array($value) ? $value : null;
    }

    private function stringArg(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function intArg(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function floatArg(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
