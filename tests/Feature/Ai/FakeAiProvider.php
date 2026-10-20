<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\AiReply;
use App\Services\Ai\Providers\AiProvider;
use RuntimeException;

/**
 * Plays back scripted replies and records what it was sent.
 */
class FakeAiProvider implements AiProvider
{
    /** @var list<array{system: string, messages: list<array<string, mixed>>, tools: list<array<string, mixed>>}> */
    public array $requests = [];

    /**
     * @param  list<AiReply>  $replies
     */
    public function __construct(private array $replies) {}

    public function generate(string $systemPrompt, array $messages, array $tools = []): AiReply
    {
        $this->requests[] = ['system' => $systemPrompt, 'messages' => $messages, 'tools' => $tools];

        return array_shift($this->replies) ?? throw new RuntimeException('No scripted reply left.');
    }

    public function listModels(): array
    {
        return ['fake-model'];
    }
}
