<?php

namespace App\Contracts\Ai;

use App\Services\Ai\Dto\AiTurn;
use App\Services\Ai\Dto\ToolDefinition;

/**
 * AiProviderInterface
 *
 * Frontera entre el orquestador del asistente y el proveedor de IA concreto.
 * Cambiar de proveedor (Gemini, OpenAI-compatible, uno local...) es
 * implementar esta interfaz y ajustar `config/ai.php`: nada más del dominio
 * conoce al proveedor.
 *
 * El contrato es deliberadamente neutral respecto a un vendor:
 *  - `contents` es una lista de mensajes en formato propio (ver
 *    App\Services\Ai\AssistantService) donde cada mensaje puede llevar texto,
 *    llamadas a herramientas (`tool_calls`) o resultados de herramientas
 *    (`tool_results`). Cada adaptador lo traduce al formato de su API.
 *  - `tools` son las herramientas expuestas al modelo, ya filtradas por la
 *    allowlist del ToolRegistry.
 *
 * El proveedor NUNCA recibe credenciales de base de datos ni acceso directo
 * a MySQL: solo el system prompt, la conversación y el catálogo de tools.
 */
interface AiProviderInterface
{
    /** Identificador legible del proveedor (para logs/meta). */
    public function name(): string;

    /**
     * Ejecuta un turno de conversación.
     *
     * @param  string  $systemInstruction  Reglas de comportamiento del asistente.
     * @param  array<int, array<string, mixed>>  $contents
     *                                                      Mensajes neutrales: ['role' => 'user'|'assistant', 'text' => ?string,
     *                                                      'tool_calls' => list<array{id:string,name:string,arguments:array}>,
     *                                                      'tool_results' => list<array{id:string,name:string,result:array}>].
     * @param  array<int, ToolDefinition>  $tools  Herramientas disponibles.
     */
    public function converse(string $systemInstruction, array $contents, array $tools): AiTurn;
}
