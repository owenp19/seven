<?php

namespace App\Services\Ai\Dto;

/**
 * ToolCall
 *
 * Petición del modelo de ejecutar una herramienta concreta. `arguments` es lo
 * que el modelo propuso: el ToolRegistry valida que la herramienta está en la
 * allowlist y la herramienta normaliza/valida sus propios argumentos, de modo
 * que un argumento inventado o malformado nunca llega crudo a la query.
 */
final class ToolCall
{
    /**
     * @param  array<string, mixed>  $arguments
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $arguments,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'arguments' => $this->arguments,
        ];
    }
}
