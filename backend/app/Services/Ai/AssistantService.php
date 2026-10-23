<?php

namespace App\Services\Ai;

use App\Contracts\Ai\AiProviderInterface;
use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Services\Ai\Dto\ToolDefinition;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AssistantService
 *
 * Orquestador del asistente. Une las piezas:
 *   conversación persistida → provider (Gemini) → herramientas (allowlist)
 *   → catálogo real → respuesta + recomendaciones/paquetes.
 *
 * El modelo NO accede a la base de datos: solo ve el system prompt, la
 * conversación y el resultado de las herramientas de solo lectura. Toda la
 * aritmética vive en PackageRecommendationService.
 *
 * Si el proveedor falla (timeout, cuota, error), se degrada con elegancia:
 * un mensaje de fallback y el catálogo normal sigue disponible en la app.
 */
class AssistantService
{
    /** Máximo de recomendaciones devueltas al cliente (las tarjetas). */
    private const MAX_RECOMMENDATIONS = 6;

    private const FALLBACK_MESSAGE = 'Ahora mismo no puedo armar una recomendación personalizada. Puedes explorar Tours, Restaurantes, Posadas y Experiencias en la app y lo intentamos de nuevo en un momento.';

    public function __construct(
        private readonly AiProviderInterface $provider,
        private readonly ToolRegistry $tools,
    ) {}

    /**
     * @return array{conversation_id:string,message:string,recommendations:array,packages:array,meta:array}
     */
    public function handle(string $message, string $installId, ?string $conversationId = null): array
    {
        $message = $this->sanitizeMessage($message);

        $conversation = $this->resolveConversation($conversationId, $installId);

        $recommendations = [];
        $packages = [];
        $fallback = false;
        $iterations = 0;

        $contents = $this->historyContents($conversation);
        $contents[] = ['role' => 'user', 'text' => $message];

        $conversation->messages()->create([
            'role' => AssistantMessage::ROLE_USER,
            'content' => $message,
        ]);

        $systemPrompt = (new SystemPrompt)->render();
        $definitions = $this->tools->definitions();

        try {
            $finalText = $this->runLoop($contents, $systemPrompt, $definitions, $recommendations, $packages, $iterations);
        } catch (\Throwable $e) {
            Log::warning('Asistente: fallo del proveedor de IA.', [
                'provider' => $this->provider->name(),
                'conversation_id' => $conversation->id,
                'exception' => $e::class,
            ]);

            $fallback = true;
            $finalText = self::FALLBACK_MESSAGE;
        }

        $conversation->messages()->create([
            'role' => AssistantMessage::ROLE_ASSISTANT,
            'content' => $finalText,
            'payload' => ['recommendations' => $recommendations, 'packages' => $packages],
        ]);

        $this->touch($conversation);

        return [
            'conversation_id' => $conversation->id,
            'message' => $finalText,
            'recommendations' => $recommendations,
            'packages' => $packages,
            'meta' => [
                'provider' => $this->provider->name(),
                'fallback' => $fallback,
                'iterations' => $iterations,
            ],
        ];
    }

    /**
     * Bucle modelo ↔ herramientas, acotado por `max_tool_iterations`.
     *
     * @param  array<int, array<string, mixed>>  $contents
     * @param  array<int, ToolDefinition>  $definitions
     * @param  array<int, array<string, mixed>>  $recommendations
     * @param  array<int, array<string, mixed>>  $packages
     */
    private function runLoop(
        array $contents,
        string $systemPrompt,
        array $definitions,
        array &$recommendations,
        array &$packages,
        int &$iterations,
    ): string {
        $max = (int) config('ai.max_tool_iterations', 4);
        $lastText = null;

        for ($i = 0; $i < $max; $i++) {
            $iterations = $i + 1;

            $turn = $this->provider->converse($systemPrompt, $contents, $definitions);

            if ($turn->text !== null && trim($turn->text) !== '') {
                $lastText = $turn->text;
            }

            if (! $turn->hasToolCalls()) {
                return $lastText ?? 'No tengo una respuesta para eso todavía. ¿Quieres que busque algo concreto?';
            }

            $contents[] = [
                'role' => 'assistant',
                'text' => $turn->text,
                'tool_calls' => array_map(fn ($call) => $call->toArray(), $turn->toolCalls),
            ];

            $toolResults = [];
            foreach ($turn->toolCalls as $call) {
                $result = $this->tools->execute($call);
                $this->collect($result, $recommendations, $packages);
                $toolResults[] = ['id' => $call->id, 'name' => $call->name, 'result' => $result];
            }

            $contents[] = ['role' => 'user', 'tool_results' => $toolResults];
        }

        // Se agotaron las iteraciones: se responde con lo último que haya dicho
        // el modelo, o con el fallback si no dijo nada útil.
        return $lastText ?? self::FALLBACK_MESSAGE;
    }

    /**
     * Recoge recomendaciones/paquetes de los resultados de herramientas para
     * devolverlos al cliente como tarjetas. Solo se aceptan ítems con id y
     * nombre reales: nada inventado llega aquí.
     *
     * @param  array<string, mixed>  $result
     * @param  array<int, array<string, mixed>>  $recommendations
     * @param  array<int, array<string, mixed>>  $packages
     */
    private function collect(array $result, array &$recommendations, array &$packages): void
    {
        $add = function (array $item) use (&$recommendations): void {
            if (! isset($item['id'], $item['name'], $item['type'])) {
                return;
            }

            $signature = $item['type'].':'.$item['id'];
            foreach ($recommendations as $existing) {
                if (($existing['type'] ?? null).':'.($existing['id'] ?? null) === $signature) {
                    return;
                }
            }

            if (count($recommendations) < self::MAX_RECOMMENDATIONS) {
                $recommendations[] = $item;
            }
        };

        if (isset($result['item']) && is_array($result['item'])) {
            $add($result['item']);
        }

        foreach (($result['items'] ?? []) as $item) {
            if (is_array($item)) {
                $add($item);
            }
        }

        foreach (($result['packages'] ?? []) as $package) {
            if (is_array($package)) {
                $packages[] = $package;
            }
        }
    }

    private function resolveConversation(?string $conversationId, string $installId): AssistantConversation
    {
        if ($conversationId === null || $conversationId === '') {
            // Se persiste de inmediato para que el ULID exista antes de
            // adjuntar mensajes (HasUlids genera la clave al guardar).
            $conversation = new AssistantConversation(['install_id' => $installId]);
            $conversation->last_activity_at = now();
            $conversation->expires_at = now()->addHours((int) config('ai.conversation.ttl_hours', 24));
            $conversation->save();

            return $conversation;
        }

        $conversation = AssistantConversation::where('install_id', $installId)
            ->where('id', $conversationId)
            ->first();

        abort_if($conversation === null, 404, 'Conversation not found.');
        abort_if($conversation->isExpired(), 404, 'Conversation not found.');

        return $conversation;
    }

    /**
     * @return array<int, array{role:string,text:string}>
     */
    private function historyContents(AssistantConversation $conversation): array
    {
        $turns = (int) config('ai.conversation.history_turns', 8);

        return $conversation->messages()
            ->latest('id')
            ->limit($turns * 2)
            ->get()
            ->reverse()
            ->filter(fn (AssistantMessage $m) => is_string($m->content) && $m->content !== '')
            ->map(fn (AssistantMessage $m) => [
                'role' => $m->role === AssistantMessage::ROLE_ASSISTANT ? 'assistant' : 'user',
                'text' => $m->content,
            ])
            ->values()
            ->all();
    }

    /**
     * Actualiza actividad, caducidad y poda los mensajes más antiguos.
     */
    private function touch(AssistantConversation $conversation): void
    {
        $conversation->last_activity_at = now();
        $conversation->expires_at = now()->addHours((int) config('ai.conversation.ttl_hours', 24));
        $conversation->save();

        $max = (int) config('ai.conversation.max_messages', 40);
        $count = $conversation->messages()->count();

        if ($count > $max) {
            $conversation->messages()
                ->orderBy('id')
                ->limit($count - $max)
                ->delete();
        }
    }

    private function sanitizeMessage(string $message): string
    {
        $message = trim($message);
        $max = (int) config('ai.max_message_chars', 1000);

        return Str::limit($message, $max, '');
    }
}
