<?php

namespace App\Services\Ai;

use App\Services\Ai\Providers\AiProvider;

/**
 * Builds the provider selected in Settings, or any provider on demand.
 */
class AiProviderManager
{
    public function __construct(private AiSettings $settings) {}

    /**
     * @throws AiException
     */
    public function current(): AiProvider
    {
        if (! $this->settings->isEnabled()) {
            throw new AiException('The assistant is turned off. Turn it on in Settings.');
        }

        $provider = $this->settings->provider();
        $apiKey = $this->settings->apiKey($provider);

        if (blank($apiKey)) {
            throw new AiException('No API key is set for the assistant. Add one in Settings.');
        }

        return $this->make($provider, $this->settings->model($provider), $apiKey, $this->settings->fallbackModel($provider));
    }

    /**
     * @throws AiException
     */
    public function make(string $provider, string $model, string $apiKey, ?string $fallbackModel = null): AiProvider
    {
        $config = $this->settings->providers()[$provider] ?? null;

        if ($config === null) {
            throw new AiException("Unknown AI provider \"{$provider}\".");
        }

        return new $config['driver']($apiKey, $model ?: $config['default_model'], [
            'base_url' => $config['base_url'] ?? null,
            'temperature' => (float) config('ai.temperature', 0.4),
            'timeout' => (int) config('ai.timeout', 60),
            'fallback_model' => $fallbackModel,
        ]);
    }
}
