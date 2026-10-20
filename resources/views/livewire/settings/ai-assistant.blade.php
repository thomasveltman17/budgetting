<form wire:submit="save" class="rounded-xl border border-line bg-surface">
    <div class="flex items-center gap-4 border-b border-line px-4 py-3">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-ink">Assistant</p>
            <p class="mt-0.5 text-[0.8125rem] text-ink-3">Opens from the sidebar or with ⌘K / Ctrl+K on any page.</p>
        </div>
        <button type="button" wire:click="$toggle('enabled')"
                role="switch" aria-checked="{{ $enabled ? 'true' : 'false' }}" aria-label="Turn the assistant on or off"
                class="relative h-5 w-9 shrink-0 rounded-full transition-colors {{ $enabled ? 'bg-accent' : 'bg-line-strong' }}">
            <span class="absolute top-0.5 left-0.5 size-4 rounded-full bg-surface shadow-sm transition-transform {{ $enabled ? 'translate-x-4' : '' }}"></span>
        </button>
    </div>

    <div class="grid gap-4 px-4 py-4 sm:grid-cols-2">
        <div>
            <x-field-label for="ai-provider">Provider</x-field-label>
            <select id="ai-provider" wire:model.live="provider" class="field">
                @foreach ($providers as $key => $config)
                    <option value="{{ $key }}">{{ $config['label'] }}</option>
                @endforeach
            </select>
            <x-field-error name="provider" />
        </div>

        <div>
            <div class="mb-1.5 flex items-baseline justify-between gap-2">
                <x-field-label for="ai-model" class="mb-0">Model</x-field-label>
                <button type="button" wire:click="loadModels" wire:loading.attr="disabled" wire:target="loadModels" class="text-xs text-ink-3 hover:text-ink">
                    <span wire:loading.remove wire:target="loadModels">Load available models</span>
                    <span wire:loading wire:target="loadModels">Loading…</span>
                </button>
            </div>
            <input id="ai-model" type="text" wire:model="model" list="ai-models" autocomplete="off" spellcheck="false" class="field font-mono text-[0.8125rem]">
            <datalist id="ai-models">
                @foreach ($availableModels as $availableModel)
                    <option value="{{ $availableModel }}"></option>
                @endforeach
            </datalist>
            @if ($availableModels !== [])
                <p class="mt-1.5 text-xs text-ink-3">{{ count($availableModels) }} models available. Start typing to pick one.</p>
            @endif
            <x-field-error name="model" />
        </div>

        <div class="sm:col-span-2">
            <x-field-label for="ai-fallback-model" optional>Fallback model</x-field-label>
            <input id="ai-fallback-model" type="text" wire:model="fallbackModel" list="ai-models" autocomplete="off" spellcheck="false"
                   placeholder="None" class="field font-mono text-[0.8125rem] sm:max-w-[calc(50%-0.5rem)]">
            <p class="mt-1.5 text-xs text-ink-3">Busy models are retried automatically. If the main model is still overloaded, the assistant answers with this one instead. Leave empty to turn it off.</p>
            <x-field-error name="fallbackModel" />
        </div>

        <div class="sm:col-span-2">
            <x-field-label for="ai-key">API key</x-field-label>
            <div class="flex gap-2">
                <input id="ai-key" type="password" wire:model="apiKey" autocomplete="off" spellcheck="false"
                       placeholder="{{ $hasApiKey ? '•••••••••••• saved. Type a new key to replace it.' : 'Paste your API key' }}"
                       class="field flex-1 font-mono text-[0.8125rem]">
                @if ($hasApiKey)
                    <button type="button" wire:click="forgetApiKey" wire:confirm="Remove the saved API key and turn the assistant off?" class="btn btn-quiet">Remove</button>
                @endif
            </div>
            <x-field-error name="apiKey" />
            <p class="mt-1.5 text-xs text-ink-3">
                Stored encrypted in the database.
                @if ($providers[$provider]['key_url'] ?? null)
                    <a href="{{ $providers[$provider]['key_url'] }}" target="_blank" rel="noopener noreferrer" class="underline hover:text-ink">Get a key</a>.
                @endif
                When you ask a question, the transactions needed to answer it are sent to {{ $providers[$provider]['label'] ?? 'the provider' }}.
            </p>
        </div>
    </div>

    @if ($testResult)
        <div class="mx-4 mb-4 rounded-lg border px-3.5 py-2.5 text-[0.8125rem] {{ $testResult['ok'] ? 'border-credit/30 bg-credit/10 text-credit' : 'border-over/30 bg-over-soft text-over' }}">
            {{ $testResult['message'] }}
        </div>
    @endif

    <div class="flex items-center justify-end gap-2 border-t border-line px-4 py-3">
        <button type="button" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection" class="btn btn-secondary btn-sm">
            <span wire:loading.remove wire:target="testConnection">Test connection</span>
            <span wire:loading wire:target="testConnection">Testing…</span>
        </button>
        <button type="submit" wire:loading.attr="disabled" wire:target="save" class="btn btn-primary btn-sm">Save</button>
    </div>
</form>
