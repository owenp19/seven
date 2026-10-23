<?php

namespace App\Services\Ai\Tools;

use App\Contracts\Ai\AssistantTool;
use App\Services\Ai\Dto\ToolDefinition;
use App\Services\Ai\PackageRecommendationService;
use App\Services\Catalog\CatalogSearchService;

/**
 * find_by_budget
 *
 * Servicios individuales que caben en un presupuesto para N personas. La
 * aritmética (precio x personas) es determinista y vive en
 * PackageRecommendationService: el modelo no calcula nada.
 */
class FindByBudgetTool implements AssistantTool
{
    public function __construct(
        private readonly PackageRecommendationService $packages,
    ) {}

    public function definition(): ToolDefinition
    {
        return new ToolDefinition(
            name: 'find_by_budget',
            description: 'Devuelve servicios individuales que caben dentro de un presupuesto (en COP) para un número de personas. El servidor calcula el coste; nunca estimes tú los totales.',
            parameters: [
                'type' => 'object',
                'properties' => [
                    'budget' => ['type' => 'integer', 'description' => 'Presupuesto total máximo en COP.'],
                    'people' => ['type' => 'integer', 'description' => 'Número de personas.'],
                    'types' => [
                        'type' => 'array',
                        'description' => 'Verticales a considerar.',
                        'items' => ['type' => 'string', 'enum' => CatalogSearchService::TYPES],
                    ],
                    'preferences' => ['type' => 'string', 'description' => 'Preferencias libres (romántico, tranquilo, cultura...).'],
                ],
                'required' => ['budget'],
            ],
        );
    }

    public function handle(array $arguments): array
    {
        $budget = is_numeric($arguments['budget'] ?? null) ? (int) $arguments['budget'] : 0;
        $people = is_numeric($arguments['people'] ?? null) ? (int) $arguments['people'] : 1;
        $types = is_array($arguments['types'] ?? null) ? $arguments['types'] : [];
        $preferences = is_string($arguments['preferences'] ?? null) ? $arguments['preferences'] : null;

        $items = $this->packages->findByBudget($budget, $people, $types, $preferences);

        return [
            'budget' => $budget,
            'people' => max(1, $people),
            'currency' => 'COP',
            'count' => count($items),
            'items' => array_map(fn ($item) => $item->toArray(), $items),
        ];
    }
}
