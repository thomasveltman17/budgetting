<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * The assistant's configuration as chosen in Settings. API keys are kept
 * per provider and encrypted with the app key.
 */
class AiSettings
{
    /**
     * @return array<string, array{label: string, driver: class-string, default_model: string, fallback_model?: string, base_url?: string, key_url?: string}>
     */
    public function providers(): array
    {
        return config('ai.providers', []);
    }

    public function isEnabled(): bool
    {
        return Setting::getValue('ai.enabled', '0') === '1';
    }

    public function provider(): string
    {
        $provider = Setting::getValue('ai.provider', config('ai.default_provider'));

        return array_key_exists($provider, $this->providers()) ? $provider : config('ai.default_provider');
    }

    public function model(?string $provider = null): string
    {
        $provider ??= $this->provider();

        return Setting::getValue("ai.{$provider}.model") ?: ($this->providers()[$provider]['default_model'] ?? '');
    }

    /**
     * The model to try when the main one is overloaded. Unset means the
     * provider's default; an empty value means no fallback.
     */
    public function fallbackModel(?string $provider = null): ?string
    {
        $provider ??= $this->provider();
        $stored = Setting::getValue("ai.{$provider}.fallback_model");

        if ($stored === null) {
            return $this->providers()[$provider]['fallback_model'] ?? null;
        }

        return $stored !== '' ? $stored : null;
    }

    public function apiKey(?string $provider = null): ?string
    {
        $provider ??= $this->provider();
        $encrypted = Setting::getValue("ai.{$provider}.api_key");

        if (blank($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    public function hasApiKey(?string $provider = null): bool
    {
        return filled($this->apiKey($provider));
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled() && $this->hasApiKey();
    }

    /**
     * Store the configuration. A null or empty API key keeps the stored one;
     * a null fallback model keeps the stored one and an empty one turns the
     * fallback off.
     */
    public function save(bool $enabled, string $provider, string $model, ?string $apiKey = null, ?string $fallbackModel = null): void
    {
        Setting::putValue('ai.enabled', $enabled ? '1' : '0');
        Setting::putValue('ai.provider', $provider);
        Setting::putValue("ai.{$provider}.model", trim($model));

        if (filled($apiKey)) {
            Setting::putValue("ai.{$provider}.api_key", Crypt::encryptString(trim($apiKey)));
        }

        if ($fallbackModel !== null) {
            Setting::putValue("ai.{$provider}.fallback_model", trim($fallbackModel));
        }
    }

    public function forgetApiKey(string $provider): void
    {
        Setting::forget("ai.{$provider}.api_key");
    }
}
