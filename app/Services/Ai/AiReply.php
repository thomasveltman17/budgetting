<?php

namespace App\Services\Ai;

/**
 * One response from a model: either text, tool calls it wants answered
 * before it continues, or both.
 */
final readonly class AiReply
{
    /**
     * @param  list<array{id: string, name: string, args: array<string, mixed>, extra: array<string, mixed>}>  $toolCalls
     */
    public function __construct(
        public ?string $text,
        public array $toolCalls = [],
    ) {}

    public function wantsTools(): bool
    {
        return $this->toolCalls !== [];
    }
}
