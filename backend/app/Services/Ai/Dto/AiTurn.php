<?php

namespace App\Services\Ai\Dto;

/**
 * AiTurn
 *
 * Resultado de un turno del proveedor. Puede traer texto (respuesta al
 * usuario) y/o una o varias llamadas a herramientas que el orquestador debe
 * ejecutar antes de volver a preguntar al modelo.
 */
final class AiTurn
{
    /**
     * @param  array<int, ToolCall>  $toolCalls
     * @param  array<string, mixed>  $usage
     */
    public function __construct(
        public readonly ?string $text,
        public readonly array $toolCalls = [],
        public readonly array $usage = [],
    ) {}

    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== [];
    }
}
