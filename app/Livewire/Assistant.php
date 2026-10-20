<?php

namespace App\Livewire;

use App\Services\Ai\AiException;
use App\Services\Ai\AiSettings;
use App\Services\Ai\AssistantService;
use App\Services\Ai\InsightService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * The chat panel available on every page. The conversation is kept in the
 * session so it survives moving between pages.
 */
class Assistant extends Component
{
    /** @var list<array{role: string, text?: string, blocks?: list<array<string, mixed>>, error?: string}> */
    public array $messages = [];

    public function mount(): void
    {
        $this->messages = session('assistant.messages', []);
    }

    #[Computed]
    public function isConfigured(): bool
    {
        return app(AiSettings::class)->isConfigured();
    }

    /**
     * @return list<array{tone: string, title: string, text: string, prompt: string}>
     */
    #[Computed]
    public function insights(): array
    {
        try {
            return app(InsightService::class)->forCurrentPeriod();
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    public function ask(string $question): void
    {
        $question = trim($question);

        validator(['question' => $question], ['question' => ['required', 'string', 'max:1000']])->validate();

        $this->messages[] = ['role' => 'user', 'text' => $question];

        try {
            $result = app(AssistantService::class)->ask($question, session('assistant.history', []));

            $this->messages[] = ['role' => 'assistant', 'blocks' => $result['blocks']];
            session(['assistant.history' => $result['history']]);
        } catch (AiException $exception) {
            $this->messages[] = ['role' => 'assistant', 'error' => $exception->getMessage()];
        } catch (Throwable $exception) {
            report($exception);

            $this->messages[] = ['role' => 'assistant', 'error' => 'Something went wrong while answering. Please try again.'];
        }

        $this->messages = array_slice($this->messages, -30);
        session(['assistant.messages' => $this->messages]);
    }

    public function newConversation(): void
    {
        $this->messages = [];
        session()->forget(['assistant.messages', 'assistant.history']);
        unset($this->insights);
    }

    public function render(): View
    {
        return view('livewire.assistant', [
            'isConfigured' => $this->isConfigured,
            'settings' => app(AiSettings::class),
        ]);
    }
}
