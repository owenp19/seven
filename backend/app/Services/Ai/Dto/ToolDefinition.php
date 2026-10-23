<?php

namespace App\Services\Ai\Dto;

/**
 * ToolDefinition
 *
 * Descripción de una herramienta tal como se le presenta al modelo. Los
 * `parameters` siguen el subconjunto de JSON Schema que entienden tanto
 * Gemini como OpenAI (type/object/properties/required/enum/items).
 */
final class ToolDefinition
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $parameters,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'parameters' => $this->parameters,
        ];
    }
}
