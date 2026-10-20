<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiException;
use App\Services\Ai\AiReply;

/**
 * A chat model the assistant can talk to. Implementations translate the
 * provider-neutral conversation below into their own API format.
 *
 * Messages are plain arrays so a conversation can live in the session:
 *  - ['role' => 'user', 'content' => string]
 *  - ['role' => 'assistant', 'content' => ?string, 'tool_calls' => list<tool call>]
 *  - ['role' => 'tool', 'results' => list<array{id: string, name: string, output: array}>]
 *
 * Tools are ['name' => string, 'description' => string, 'parameters' => ?array]
 * where parameters is a JSON schema object.
 */
interface AiProvider
{
    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array{name: string, description: string, parameters?: array<string, mixed>|null}>  $tools
     *
     * @throws AiException
     */
    public function generate(string $systemPrompt, array $messages, array $tools = []): AiReply;

    /**
     * Model identifiers this key can use for chat, best effort.
     *
     * @return list<string>
     */
    public function listModels(): array;
}
