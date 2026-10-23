<?php

namespace App\Services\Ai\Tools;

use App\Contracts\Ai\AssistantTool;
use App\Services\Ai\Dto\ToolDefinition;
use App\Services\Ai\PackageRecommendationService;
use App\Services\Catalog\CatalogItemDTO;
use App\Services\Catalog\CatalogSearchService;

/**
 * build_package
 *
 * Arma combinaciones de servicios (un plan) que no superan el presupuesto
 * para N personas. Determinista: el total y el restante los calcula el
 * servidor. Nunca propone un paquete por encima del presupuesto.
 */
class BuildPackageTool implements AssistantTool
{
    public function __construct(
        private readonly PackageRecommendationService $packages,
    ) {}

    public function definition(): ToolDefinition
    {
        return new ToolDefinition(
            name: 'build_package',
            description: 'Construye uno o varios planes (combinaciones de servicios) que caben en un presupuesto (COP) para un número de personas. El servidor calcula total y restante; no los estimes tú.',
            parameters: [
                'type' => 'object',
                'properties' => [
                    'budget' => ['type' => 'integer', 'description' => 'Presupuesto total máximo en COP.'],
                    'people' => ['type' => 'integer', 'description' => 'Número de personas.'],
                    'types' => [
                        'type' => 'array',
                        'description' => 'Verticales a combinar (p. ej. tour + experience).',
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

        $packages = $this->packages->buildPackage($budget, $people, $types, $preferences);

        return [
            'budget' => $budget,
            'people' => max(1, $people),
            'currency' => 'COP',
            'count' => count($packages),
            'packages' => array_map(fn (array $package): array => [
                'total' => $package['total'],
                'remaining' => $package['remaining'],
                'items' => array_map(fn (CatalogItemDTO $item): array => $item->toArray(), $package['items']),
            ], $packages),
        ];
    }
}
