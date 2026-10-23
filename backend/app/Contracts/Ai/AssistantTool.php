<?php

namespace App\Contracts\Ai;

use App\Services\Ai\Dto\ToolDefinition;

/**
 * AssistantTool
 *
 * Herramienta de SOLO LECTURA que el modelo puede invocar. Cada tool declara
 * su esquema (para que el modelo sepa cómo llamarla) y ejecuta una consulta
 * acotada contra el catálogo real de SEVEN. Ningún tool escribe datos ni
 * ejecuta SQL libre.
 */
interface AssistantTool
{
    /** Definición (nombre, descripción, esquema JSON de parámetros). */
    public function definition(): ToolDefinition;

    /**
     * Ejecuta la herramienta con los argumentos ya validados/normalizados.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed> Resultado estructurado (se envía al modelo
     *                              y, en parte, al cliente).
     */
    public function handle(array $arguments): array;
}
