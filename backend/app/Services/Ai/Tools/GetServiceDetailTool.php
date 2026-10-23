<?php

namespace App\Services\Ai\Tools;

use App\Contracts\Ai\AssistantTool;
use App\Services\Ai\Dto\ToolDefinition;
use App\Services\Catalog\CatalogSearchService;

/**
 * get_service_detail
 *
 * Ficha completa de un servicio concreto del catálogo. Sirve para ampliar
 * datos (descripción, ubicación, valoración...) antes de recomendar.
 */
class GetServiceDetailTool implements AssistantTool
{
    public function __construct(
        private readonly CatalogSearchService $catalog,
    ) {}

    public function definition(): ToolDefinition
    {
        return new ToolDefinition(
            name: 'get_service_detail',
            description: 'Devuelve la ficha de un servicio concreto del catálogo por su tipo y su clave (slug para tour/restaurante/posada/panadería, id numérico para experiencia/alojamiento/negocio).',
            parameters: [
                'type' => 'object',
                'properties' => [
                    'type' => ['type' => 'string', 'enum' => CatalogSearchService::TYPES],
                    'key' => ['type' => 'string', 'description' => 'Slug o id del servicio.'],
                ],
                'required' => ['type', 'key'],
            ],
        );
    }

    public function handle(array $arguments): array
    {
        $type = is_string($arguments['type'] ?? null) ? trim($arguments['type']) : '';
        $key = is_string($arguments['key'] ?? null) ? trim($arguments['key']) : '';

        if ($type === '' || $key === '') {
            return ['found' => false, 'item' => null];
        }

        $item = $this->catalog->detail($type, $key);

        return $item === null
            ? ['found' => false, 'item' => null]
            : ['found' => true, 'item' => $item->toArray()];
    }
}
