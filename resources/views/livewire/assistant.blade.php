@php
    $starters = [
        'What were my three biggest expenses this period?',
        'Where could I cut back this period?',
        'How does my spending compare with the last few periods?',
        'Which subscriptions am I paying for?',
    ];
@endphp

<div x-data="{
        draft: '',
        pending: null,
        send(question = null) {
            question = (question ?? this.draft).trim();
            if (! question || this.pending !== null) return;
            this.pending = question;
            this.draft = '';
            this.$nextTick(() => { this.resize(); this.scrollDown(); });
            this.$wire.ask(question).finally(() => {
                this.pending = null;
                this.$nextTick(() => { this.scrollDown(); this.$refs.input?.focus(); });
            });
        },
        scrollDown() {
            this.$refs.scroller?.scrollTo({ top: this.$refs.scroller.scrollHeight, behavior: 'smooth' });
        },
        resize() {
            const input = this.$refs.input;
            if (! input) return;
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 160) + 'px';
        },
     }"
     x-init="$watch('assistantOpen', open => open && $nextTick(() => { scrollDown(); $refs.input?.focus(); }))"
     x-show="assistantOpen"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="translate-x-full opacity-0"
     x-transition:enter-end="translate-x-0 opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="translate-x-0 opacity-100"
     x-transition:leave-end="translate-x-full opacity-0"
     @keydown.escape.window="assistantOpen = false"
     role="dialog"
     aria-label="Assistant"
     class="fixed inset-y-0 right-0 z-[150] flex w-full flex-col border-l border-line bg-canvas shadow-[-24px_0_48px_-24px_rgb(0_0_0/0.35)] sm:w-[30rem]">

    {{-- Header --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-line px-4">
        <span class="grid size-7 place-items-center rounded-md bg-accent-soft text-accent">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" /></svg>
        </span>
        <div class="min-w-0 flex-1">
            <h2 class="text-[0.9375rem] font-semibold text-ink">Assistant</h2>
            @if ($isConfigured)
                <p class="truncate text-xs text-ink-3">{{ $settings->providers()[$settings->provider()]['label'] ?? '' }} · {{ $settings->model() }}</p>
            @endif
        </div>
        @if ($messages !== [])
            <button type="button" wire:click="newConversation" :disabled="pending !== null" class="btn btn-quiet btn-sm" title="Start a new conversation">New chat</button>
        @endif
        <button type="button" @click="assistantOpen = false" class="icon-btn" title="Close (Esc)">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>

    {{-- Conversation --}}
    <div x-ref="scroller" class="flex-1 space-y-5 overflow-y-auto px-4 py-5">
        @if (! $isConfigured)
            <div class="rounded-xl border border-line bg-surface p-5 text-sm">
                <p class="font-medium text-ink">Set up the assistant</p>
                <p class="mt-1 text-ink-2">Choose an AI provider and add an API key to start asking questions about your spending.</p>
                <a href="{{ route('settings') }}#assistant" class="btn btn-primary btn-sm mt-4">Open settings</a>
            </div>
        @elseif ($messages === [])
            <div x-show="pending === null">
                <p class="text-sm text-ink-2">Ask anything about your money, or start with what stands out this period.</p>

                @if ($this->insights !== [])
                    <div class="mt-4 space-y-2">
                        @foreach ($this->insights as $insight)
                            @php
                                $dot = ['insight' => 'bg-accent', 'tip' => 'bg-attention', 'warning' => 'bg-over', 'positive' => 'bg-credit'][$insight['tone']] ?? 'bg-accent';
                            @endphp
                            <button type="button" @click="send(@js($insight['prompt']))"
                                    class="group flex w-full gap-3 rounded-lg border border-line bg-surface px-3.5 py-3 text-left transition-colors hover:border-line-strong">
                                <span class="mt-1.5 size-1.5 shrink-0 rounded-full {{ $dot }}"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-ink">{{ $insight['title'] }}</span>
                                    <span class="mt-0.5 block text-[0.8125rem] text-ink-2">{{ $insight['text'] }}</span>
                                </span>
                                <svg class="mt-1 size-4 shrink-0 text-ink-3 transition-transform group-hover:translate-x-0.5 group-hover:text-ink" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </button>
                        @endforeach
                    </div>
                @endif

                <p class="mt-6 text-xs font-medium text-ink-3">Try asking</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($starters as $starter)
                        <button type="button" @click="send(@js($starter))"
                                class="rounded-full border border-line bg-surface px-3 py-1 text-left text-xs text-ink-2 transition-colors hover:border-accent/50 hover:text-ink">{{ $starter }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        @foreach ($messages as $message)
            @if ($message['role'] === 'user')
                <div class="flex justify-end" wire:key="message-{{ $loop->index }}">
                    <p class="max-w-[85%] rounded-2xl rounded-br-md bg-accent px-3.5 py-2 text-sm whitespace-pre-line text-[var(--c-on-accent)]">{{ $message['text'] }}</p>
                </div>
            @elseif (isset($message['error']))
                <div class="rounded-lg border border-over/30 bg-over-soft px-3.5 py-3 text-sm text-over" wire:key="message-{{ $loop->index }}">
                    {{ $message['error'] }}
                    @if (str_contains($message['error'], 'Settings'))
                        <a href="{{ route('settings') }}#assistant" class="ml-1 underline">Open settings</a>
                    @endif
                </div>
            @else
                <div class="space-y-3" wire:key="message-{{ $loop->index }}">
                    @foreach ($message['blocks'] as $block)
                        <x-assistant-block :block="$block" />
                    @endforeach
                </div>
            @endif
        @endforeach

        <template x-if="pending !== null">
            <div class="space-y-5">
                <div class="flex justify-end">
                    <p class="max-w-[85%] rounded-2xl rounded-br-md bg-accent px-3.5 py-2 text-sm whitespace-pre-line text-[var(--c-on-accent)]" x-text="pending"></p>
                </div>
                <div class="flex items-center gap-2 text-xs text-ink-3" aria-live="polite">
                    <span class="flex gap-1">
                        <span class="size-1.5 animate-bounce rounded-full bg-ink-3 [animation-delay:-0.3s]"></span>
                        <span class="size-1.5 animate-bounce rounded-full bg-ink-3 [animation-delay:-0.15s]"></span>
                        <span class="size-1.5 animate-bounce rounded-full bg-ink-3"></span>
                    </span>
                    Looking through your transactions…
                </div>
            </div>
        </template>
    </div>

    {{-- Composer --}}
    @if ($isConfigured)
        <form @submit.prevent="send()" class="shrink-0 border-t border-line p-3">
            <div class="flex items-end gap-2 rounded-xl border border-line-strong bg-surface p-1.5 focus-within:border-accent">
                <textarea x-ref="input" x-model="draft" rows="1" maxlength="1000"
                          @input="resize()"
                          @keydown.enter="if (! $event.shiftKey && ! $event.isComposing) { $event.preventDefault(); send(); }"
                          placeholder="Ask about your spending…"
                          aria-label="Ask the assistant"
                          class="max-h-40 min-h-9 flex-1 resize-none bg-transparent px-2 py-1.5 text-sm text-ink placeholder:text-ink-3 focus:outline-none"></textarea>
                <button type="submit" :disabled="pending !== null || draft.trim() === ''" title="Send (Enter)"
                        class="grid size-8 shrink-0 place-items-center rounded-lg bg-accent text-[var(--c-on-accent)] transition-opacity disabled:opacity-40">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" /></svg>
                </button>
            </div>
            <p class="mt-1.5 px-1 text-[0.6875rem] text-ink-3">Enter to send · Shift+Enter for a new line · Answers can be wrong, so check important numbers.</p>
        </form>
    @endif
</div>
