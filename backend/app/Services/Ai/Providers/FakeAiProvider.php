<?php

namespace App\Services\Ai\Providers;

use App\Contracts\Ai\AiProviderInterface;
use App\Services\Ai\Dto\AiTurn;
use App\Services\Ai\Dto\ToolDefinition;

/**
 * FakeAiProvider
 *
 * Proveedor determinista sin red. Se usa en los tests (y en demos offline)
 * para controlar exactamente los turnos del modelo sin llamar a ningún
 * servicio externo. Recoge las llamadas recibidas para poder inspeccionarlas
 * (p. ej. comprobar que el catálogo viaja como DATO, no como instrucciones).
 */
class FakeAiProvider implements AiProviderInterface
{
    /** @var array<int, AiTurn> */
    private array $queue = [];

    /** @var array<int, array{systemInstruction:string, contents:array, tools:array}> */
    public array $calls = [];

    public function name(): string
    {
        return 'fake';
    }

    public function push(AiTurn ...$turns): void
    {
        foreach ($turns as $turn) {
            $this->queue[] = $turn;
        }
    }

    public function converse(string $systemInstruction, array $contents, array $tools): AiTurn
    {
        $this->calls[] = [
            'systemInstruction' => $systemInstruction,
            'contents' => $contents,
            'tools' => $tools,
        ];

        return array_shift($this->queue) ?? new AiTurn(text: 'Listo, ¿en qué más te ayudo?');
    }

    /** @return array<int, ToolDefinition> */
    public function lastTools(): array
    {
        return $this->calls === [] ? [] : end($this->calls)['tools'];
    }
}
