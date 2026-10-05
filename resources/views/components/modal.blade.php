@props(['title', 'close', 'subtitle' => null, 'width' => 'max-w-lg'])

<div class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-6"
     x-data x-on:keydown.escape.window="$wire.{{ $close }}()"
     role="dialog" aria-modal="true" aria-label="{{ $title }}">
    <div class="absolute inset-0 bg-black/45" wire:click="{{ $close }}"></div>

    <div {{ $attributes->class(['relative flex max-h-[88vh] w-full flex-col overflow-hidden rounded-t-xl border border-line bg-surface shadow-[0_24px_48px_-12px_rgb(0_0_0/0.35)] sm:rounded-xl', $width]) }}>
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-line px-5 py-4">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-ink">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="mt-0.5 text-sm text-ink-3">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" wire:click="{{ $close }}" class="icon-btn -mr-1.5" title="Close">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-line bg-sunken/60 px-5 py-3">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
