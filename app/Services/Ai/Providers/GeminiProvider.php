<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiException;
use App\Services\Ai\AiReply;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Google Gemini through the Generative Language API, with function calling.
 */
class GeminiProvider implements AiProvider
{
    /**
     * Overloaded, rate limited or briefly unavailable: worth trying again.
     */
    private const TEMPORARY_STATUSES = [429, 500, 502, 503, 504];

    /**
     * @param  array{base_url?: string, temperature?: float, timeout?: int, fallback_model?: ?string, retry_delays?: list<int>}  $options
     */
    public function __construct(
        private string $apiKey,
        private string $model,
        private array $options = [],
    ) {}

    /**
     * Retries temporary failures, then moves to the fallback model if the
     * main one is still overloaded. Once the fallback has answered it keeps
     * being used for the rest of the conversation.
     */
    public function generate(string $systemPrompt, array $messages, array $tools = []): AiReply
    {
        $payload = [
            'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => $this->contents($messages),
            'generationConfig' => ['temperature' => $this->options['temperature'] ?? 0.4],
        ];

        if ($tools !== []) {
            $payload['tools'] = [['functionDeclarations' => array_map($this->functionDeclaration(...), $tools)]];
        }

        try {
            return $this->generateWith($this->model, $payload);
        } catch (AiException $exception) {
            $fallback = $this->options['fallback_model'] ?? null;

            if (! $exception->isTemporary || blank($fallback) || $fallback === $this->model) {
                throw $exception;
            }

            try {
                $reply = $this->generateWith($fallback, $payload);
            } catch (AiException $fallbackException) {
                throw new AiException(
                    $fallbackException->isTemporary
                        ? "Gemini is overloaded right now: both {$this->model} and {$fallback} are busy. This is usually temporary, so try again in a minute."
                        : $exception->getMessage().' The fallback model could not be used either: '.$fallbackException->getMessage(),
                    true,
                );
            }

            $this->model = $fallback;

            return $reply;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function generateWith(string $model, array $payload): AiReply
    {
        $path = 'models/'.rawurlencode(Str::after($model, 'models/')).':generateContent';
        $response = $this->send(fn (PendingRequest $request) => $request->post($path, $payload), $model);

        return $this->parseReply($response->json() ?? []);
    }

    public function listModels(): array
    {
        try {
            $response = $this->send(fn (PendingRequest $request) => $request->get('models', ['pageSize' => 200]));
        } catch (AiException) {
            return [];
        }

        return collect($response->json('models', []))
            ->filter(fn (array $model) => in_array('generateContent', $model['supportedGenerationMethods'] ?? [], true))
            ->map(fn (array $model) => Str::after($model['name'] ?? '', 'models/'))
            ->filter(fn (string $name) => str_starts_with($name, 'gemini'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     */
    private function send(callable $call, ?string $model = null): Response
    {
        $model ??= $this->model;

        $request = Http::baseUrl($this->options['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta')
            ->withHeaders(['x-goog-api-key' => $this->apiKey])
            ->acceptJson()
            ->timeout($this->options['timeout'] ?? 60)
            ->retry(
                $this->options['retry_delays'] ?? [1000, 3000],
                when: fn (Throwable $exception) => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && in_array($exception->response->status(), self::TEMPORARY_STATUSES, true)),
                throw: false,
            );

        try {
            $response = $call($request);
        } catch (ConnectionException) {
            throw new AiException('Could not reach Google Gemini. Check your internet connection and try again.');
        }

        if ($response->failed()) {
            $status = $response->status();
            $message = $response->json('error.message') ?: 'HTTP '.$status;

            throw new AiException(match ($status) {
                400, 403 => "Gemini rejected the request: {$message}",
                404 => "Gemini does not know the model \"{$model}\". Pick another model in Settings.",
                429 => 'Gemini rate limit or quota reached. Wait a moment and try again.',
                500, 502, 503, 504 => "Gemini's {$model} is overloaded right now. This is usually temporary, so try again in a minute, or set a fallback model in Settings.",
                default => "Gemini returned an error: {$message}",
            }, isTemporary: in_array($status, self::TEMPORARY_STATUSES, true));
        }

        return $response;
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return list<array<string, mixed>>
     */
    private function contents(array $messages): array
    {
        $contents = [];

        foreach ($messages as $message) {
            $contents[] = match ($message['role']) {
                'user' => ['role' => 'user', 'parts' => [['text' => (string) $message['content']]]],
                'assistant' => ['role' => 'model', 'parts' => $this->modelParts($message)],
                'tool' => ['role' => 'user', 'parts' => array_map(fn (array $result) => ['functionResponse' => array_filter([
                    'id' => $result['id'] ?? null,
                    'name' => $result['name'],
                    'response' => ['result' => $result['output']],
                ], fn ($value) => $value !== null)], $message['results'])],
            };
        }

        return $contents;
    }

    /**
     * Gemini needs each function call echoed back exactly as it was sent,
     * including the thought signature thinking models attach to it.
     *
     * @param  array<string, mixed>  $message
     * @return list<array<string, mixed>>
     */
    private function modelParts(array $message): array
    {
        $parts = [];

        if (filled($message['content'] ?? null)) {
            $parts[] = ['text' => $message['content']];
        }

        foreach ($message['tool_calls'] ?? [] as $call) {
            $part = ['functionCall' => array_filter([
                'id' => str_starts_with($call['id'], 'call_') ? null : $call['id'],
                'name' => $call['name'],
                'args' => (object) $call['args'],
            ], fn ($value) => $value !== null)];

            if (isset($call['extra']['thoughtSignature'])) {
                $part['thoughtSignature'] = $call['extra']['thoughtSignature'];
            }

            $parts[] = $part;
        }

        return $parts === [] ? [['text' => '']] : $parts;
    }

    /**
     * @param  array{name: string, description: string, parameters?: array<string, mixed>|null}  $tool
     * @return array<string, mixed>
     */
    private function functionDeclaration(array $tool): array
    {
        return array_filter([
            'name' => $tool['name'],
            'description' => $tool['description'],
            'parameters' => $tool['parameters'] ?? null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function parseReply(array $body): AiReply
    {
        $candidate = $body['candidates'][0] ?? null;
        $parts = $candidate['content']['parts'] ?? [];

        if ($parts === []) {
            $reason = $candidate['finishReason'] ?? ($body['promptFeedback']['blockReason'] ?? 'unknown');

            throw new AiException("Gemini returned no answer (reason: {$reason}). Try rephrasing the question.");
        }

        $text = [];
        $toolCalls = [];

        foreach ($parts as $part) {
            if (isset($part['functionCall'])) {
                $toolCalls[] = [
                    'id' => (string) ($part['functionCall']['id'] ?? 'call_'.Str::random(8)),
                    'name' => (string) $part['functionCall']['name'],
                    'args' => $this->normaliseArgs($part['functionCall']['args'] ?? []),
                    'extra' => isset($part['thoughtSignature']) ? ['thoughtSignature' => $part['thoughtSignature']] : [],
                ];
            } elseif (isset($part['text']) && ! ($part['thought'] ?? false)) {
                $text[] = $part['text'];
            }
        }

        return new AiReply(
            text: $text === [] ? null : implode('', $text),
            toolCalls: $toolCalls,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function normaliseArgs(mixed $args): array
    {
        try {
            return is_array($args) ? $args : (array) json_decode(json_encode($args), true);
        } catch (Throwable) {
            return [];
        }
    }
}
