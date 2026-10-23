<?php

namespace App\Services\Ai;

use App\Contracts\Ai\AssistantTool;
use App\Services\Ai\Dto\ToolCall;
use App\Services\Ai\Dto\ToolDefinition;

/**
 * ToolRegistry
 *
 * Allowlist de herramientas expuestas al modelo. El modelo SOLO puede invocar
 * lo que aquí está registrado; cualquier nombre de herramienta que no exista
 * se ignora (nunca se ejecuta nada arbitrario). Es el punto único donde se
 * añade o quita una capacidad del asistente.
 */
class ToolRegistry
{
    /** @var array<string, AssistantTool> */
    private array $tools = [];

    /**
     * @param  iterable<AssistantTool>  $tools
     */
    public function __construct(iterable $tools = [])
    {
        foreach ($tools as $tool) {
            $this->register($tool);
        }
    }

    public function register(AssistantTool $tool): void
    {
        $this->tools[$tool->definition()->name] = $tool;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * @return array<int, ToolDefinition>
     */
    public function definitions(): array
    {
        return array_values(array_map(
            fn (AssistantTool $tool): ToolDefinition => $tool->definition(),
            $this->tools,
        ));
    }

    /**
     * Ejecuta una llamada a herramienta. Devuelve un error estructurado si la
     * herramienta no está registrada, de modo que el modelo pueda corregir en
     * el siguiente turno en vez de romper la conversación.
     *
     * @return array<string, mixed>
     */
    public function execute(ToolCall $call): array
    {
        if (! $this->has($call->name)) {
            return ['error' => 'unknown_tool', 'message' => "La herramienta [{$call->name}] no existe."];
        }

        try {
            return $this->tools[$call->name]->handle($call->arguments);
        } catch (\Throwable $e) {
            report($e);

            return ['error' => 'tool_failed', 'message' => 'No fue posible consultar el catálogo.'];
        }
    }
}
