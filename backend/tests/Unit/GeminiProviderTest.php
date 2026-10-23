<?php

namespace Tests\Unit;

use App\Services\Ai\Dto\ToolDefinition;
use App\Services\Ai\Providers\GeminiProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.provider' => 'gemini',
            'ai.gemini.api_key' => 'test-key',
            'ai.gemini.model' => 'gemini-1.5-flash',
            'ai.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        ]);
    }

    public function test_it_parses_text_reply(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Hola viajero']]]]],
                'usageMetadata' => ['totalTokenCount' => 12],
            ]),
        ]);

        $turn = (new GeminiProvider)->converse('system', [['role' => 'user', 'text' => 'Hola']], []);

        $this->assertSame('Hola viajero', $turn->text);
        $this->assertFalse($turn->hasToolCalls());
        $this->assertSame(12, $turn->usage['totalTokenCount']);
    }

    public function test_it_parses_function_calls(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [['content' => ['parts' => [[
                    'functionCall' => ['name' => 'find_by_budget', 'args' => ['budget' => 500000, 'people' => 2]],
                ]]]]],
            ]),
        ]);

        $turn = (new GeminiProvider)->converse('system', [['role' => 'user', 'text' => 'plan']], []);

        $this->assertTrue($turn->hasToolCalls());
        $this->assertSame('find_by_budget', $turn->toolCalls[0]->name);
        $this->assertSame(500000, $turn->toolCalls[0]->arguments['budget']);
    }

    public function test_it_sends_system_instruction_tools_and_uppercased_schema(): void
    {
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'ok']]]]]])]);

        $tool = new ToolDefinition(
            name: 'find_by_budget',
            description: 'x',
            parameters: [
                'type' => 'object',
                'properties' => ['budget' => ['type' => 'integer']],
                'required' => ['budget'],
            ],
        );

        (new GeminiProvider)->converse('SISTEMA', [['role' => 'user', 'text' => 'hola']], [$tool]);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['systemInstruction']['parts'][0]['text'] === 'SISTEMA'
                && $body['tools'][0]['functionDeclarations'][0]['name'] === 'find_by_budget'
                && $body['tools'][0]['functionDeclarations'][0]['parameters']['type'] === 'OBJECT'
                && $body['tools'][0]['functionDeclarations'][0]['parameters']['properties']['budget']['type'] === 'INTEGER';
        });
    }
}
