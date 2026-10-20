<?php

namespace App\Livewire\Settings;

use App\Services\Ai\AiException;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\AiSettings;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class AiAssistant extends Component
{
    public bool $enabled = false;

    public string $provider = '';

    public string $model = '';

    public string $fallbackModel = '';

    public string $apiKey = '';

    /** @var list<string> */
    public array $availableModels = [];

    /** @var array{ok: bool, message: string}|null */
    public ?array $testResult = null;

    public function mount(AiSettings $settings): void
    {
        $this->enabled = $settings->isEnabled();
        $this->provider = $settings->provider();
        $this->model = $settings->model($this->provider);
        $this->fallbackModel = $settings->fallbackModel($this->provider) ?? '';
    }

    public function updatedProvider(AiSettings $settings): void
    {
        $this->model = $settings->model($this->provider);
        $this->fallbackModel = $settings->fallbackModel($this->provider) ?? '';
        $this->reset(['apiKey', 'availableModels', 'testResult']);
    }

    public function save(AiSettings $settings): void
    {
        $this->validate($this->rules());

        if ($this->enabled && blank($this->apiKey) && ! $settings->hasApiKey($this->provider)) {
            $this->addError('apiKey', 'Add an API key to turn the assistant on.');

            return;
        }

        $settings->save($this->enabled, $this->provider, $this->model, $this->apiKey, $this->fallbackModel);

        $this->reset(['apiKey', 'testResult']);
        $this->dispatch('toast', type: 'success', message: 'Assistant settings saved.');
    }

    public function forgetApiKey(AiSettings $settings): void
    {
        $settings->forgetApiKey($this->provider);
        $settings->save(false, $this->provider, $this->model);

        $this->enabled = false;
        $this->reset(['apiKey', 'testResult', 'availableModels']);
        $this->dispatch('toast', type: 'info', message: 'API key removed. The assistant is off.');
    }

    public function testConnection(AiSettings $settings, AiProviderManager $providers): void
    {
        $this->validate($this->rules());

        $apiKey = filled($this->apiKey) ? trim($this->apiKey) : $settings->apiKey($this->provider);

        if (blank($apiKey)) {
            $this->testResult = ['ok' => false, 'message' => 'Enter an API key first.'];

            return;
        }

        try {
            $reply = $providers->make($this->provider, $this->model, $apiKey)
                ->generate('You are a connection check. Reply with the single word OK.', [['role' => 'user', 'content' => 'Ping']]);

            $this->testResult = ['ok' => true, 'message' => 'Connected to '.$this->model.'. Replied: “'.str($reply->text ?? '')->trim()->limit(40).'”'];
        } catch (AiException $exception) {
            $this->testResult = ['ok' => false, 'message' => $exception->getMessage()];
        }
    }

    public function loadModels(AiSettings $settings, AiProviderManager $providers): void
    {
        $apiKey = filled($this->apiKey) ? trim($this->apiKey) : $settings->apiKey($this->provider);

        if (blank($apiKey)) {
            $this->addError('apiKey', 'Enter an API key to load the available models.');

            return;
        }

        $this->availableModels = $providers->make($this->provider, $this->model, $apiKey)->listModels();

        if ($this->availableModels === []) {
            $this->addError('model', 'Could not load models with this key. You can still type a model name.');
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'provider' => ['required', Rule::in(array_keys(app(AiSettings::class)->providers()))],
            'model' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._\-\/]+$/'],
            'fallbackModel' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._\-\/]+$/', 'different:model'],
            'apiKey' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function render(AiSettings $settings): View
    {
        return view('livewire.settings.ai-assistant', [
            'providers' => $settings->providers(),
            'hasApiKey' => $settings->hasApiKey($this->provider),
        ]);
    }
}
