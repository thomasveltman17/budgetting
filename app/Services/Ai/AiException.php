<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * A failure talking to an AI provider, with a message that is safe to show
 * to the user. Temporary failures (overload, rate limits) may succeed when
 * tried again or with another model.
 */
class AiException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $isTemporary = false)
    {
        parent::__construct($message);
    }
}
