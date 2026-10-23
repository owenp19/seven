<?php

namespace App\Services\Ai\Providers;

use App\Contracts\Ai\AiProviderInterface;
use App\Services\Ai\Dto\AiTurn;
use App\Services\Ai\Dto\ToolCall;
use App\Services\Ai\Dto\ToolDefinition;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * GeminiProvider
 *
 * Adaptador de Google AI (Generative Language API). Traduce el formato neutral
 * del orquestador al de Gemini (contents/systemInstruction/tools) y viceversa.
 *
 * No se usa el SDK de Google: solo HTTP con la API key del backend. La clave
 * nunca se registra en logs ni se devuelve al cliente.
 */
class GeminiProvider implements AiProviderInterface
{
    public function name(): string
    {
        return 'gemini';
    }

    public function converse(string $systemInstruction, array $contents, array $tools): AiTurn
    {
        $config = config('ai.gemini');
        $apiKey = (string) ($config['api_key'] ?? '');

        if ($apiKey === '') {
            throw new \RuntimeException('GEMINI_API_KEY no está configurada.');
        }

        $body = [
            'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
            'contents' => $this->toGeminiContents($contents),
            'generationConfig' => [
                'temperature' => (float) ($config['temperature'] ?? 0.4),
                'maxOutputTokens' => (int) ($config['max_output_tokens'] ?? 1024),
            ],
        ];

        if ($tools !== []) {
            $body['tools'] = [[
                'functionDeclarations' => array_map(
                    fn (ToolDefinition $tool): array => $this->toGeminiFunction($tool),
                    $tools,
                ),
            ]];
        }

        $url = rtrim((string) $config['base_url'], '/').'/models/'.$config['model'].':generateContent';

        $response = Http::timeout((int) ($config['timeout'] ?? 30))
            ->acceptJson()
            ->post($url.'?key='.$apiKey, $body);

        if ($response->failed()) {
            throw new \RuntimeException('Gemini respondió con estado '.$response->status());
        }

        return $this->parse($response->json() ?? []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $contents
     * @return array<int, array<string, mixed>>
     */
    private function toGeminiContents(array $contents): array
    {
        $normalized = [];

        foreach ($contents as $message) {
            $role = ($message['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $parts = [];

            if (! empty($message['tool_results'])) {
                foreach ($message['tool_results'] as $result) {
                    $parts[] = [
                        'functionResponse' => [
                            'name' => (string) ($result['name'] ?? ''),
                            'response' => is_array($result['result'] ?? null) ? $result['result'] : [],
                        ],
                    ];
                }
            }

            if (! empty($message['tool_calls'])) {
                foreach ($message['tool_calls'] as $call) {
                    $parts[] = [
                        'functionCall' => [
                            'name' => (string) ($call['name'] ?? ''),
                            'args' => is_array($call['arguments'] ?? null) ? $call['arguments'] : [],
                        ],
                    ];
                }
            }

            if (is_string($message['text'] ?? null) && trim($message['text']) !== '') {
                $parts[] = ['text' => $message['text']];
            }

            if ($parts === []) {
                continue;
            }

            $normalized[] = ['role' => $role, 'parts' => $parts];
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function toGeminiFunction(ToolDefinition $tool): array
    {
        return [
            'name' => $tool->name,
            'description' => $tool->description,
            'parameters' => $this->uppercaseTypes($tool->parameters),
        ];
    }

    /**
     * Gemini espera los tipos de esquema en mayúsculas (OBJECT, STRING, ...).
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function uppercaseTypes(array $schema): array
    {
        if (isset($schema['type']) && is_string($schema['type'])) {
            $schema['type'] = strtoupper($schema['type']);
        }

        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $key => $property) {
                if (is_array($property)) {
                    $schema['properties'][$key] = $this->uppercaseTypes($property);
                }
            }
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = $this->uppercaseTypes($schema['items']);
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function parse(array $payload): AiTurn
    {
        $candidate = $payload['candidates'][0] ?? [];
        $parts = $candidate['content']['parts'] ?? [];

        $text = [];
        $toolCalls = [];

        foreach ($parts as $part) {
            if (isset($part['text']) && is_string($part['text'])) {
                $text[] = $part['text'];
            }

            if (isset($part['functionCall']['name'])) {
                $toolCalls[] = new ToolCall(
                    id: 'call_'.Str::lower(Str::random(10)),
                    name: (string) $part['functionCall']['name'],
                    arguments: is_array($part['functionCall']['args'] ?? null) ? $part['functionCall']['args'] : [],
                );
            }
        }

        $usage = $payload['usageMetadata'] ?? [];

        return new AiTurn(
            text: $text === [] ? null : trim(implode("\n", $text)),
            toolCalls: $toolCalls,
            usage: is_array($usage) ? $usage : [],
        );
    }
}
