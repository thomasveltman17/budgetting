<?php

use App\Models\Period;
use App\Services\PeriodService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public int $selectedPeriodId;

    public string $pageUrl = '';

    public function mount(): void
    {
        $this->pageUrl = url()->current();
        $this->selectedPeriodId = app(PeriodService::class)->getSelectedPeriod()->id;
    }

    #[Computed]
    public function selectedPeriod(): Period
    {
        return Period::find($this->selectedPeriodId);
    }

    #[Computed]
    public function periods(): \Illuminate\Support\Collection
    {
        return app(PeriodService::class)->getRecentPeriods(6)
            ->filter(fn ($p) => $p->id !== $this->selectedPeriodId)
            ->values();
    }

    public function switchPeriod(int $periodId): void
    {
        app(PeriodService::class)->switchPeriod($periodId);
        $this->redirect($this->pageUrl ?: route('dashboard'));
    }
};
?>

@php
    $selected = $this->selectedPeriod;
    $length = $selected?->lengthInDays() ?? 0;
    $elapsed = $selected?->elapsedDays() ?? 0;
@endphp

<div x-data="{ open: false }" @click.outside="open = false" class="text-rail-ink-2">
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        class="group w-full px-5 py-4 text-left transition-colors hover:bg-white/[0.04]"
    >
        <span class="flex items-center justify-between gap-2 text-xs">
            <span>Period</span>
            <svg class="size-3.5 transition-transform duration-150" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" /></svg>
        </span>

        @if ($selected)
            <span class="mt-1 block text-sm font-medium text-rail-ink">
                {{ $selected->start_date->format('j M') }} – {{ $selected->end_date->format('j M Y') }}
            </span>

            <span class="mt-3 flex h-1 gap-[2px]" aria-hidden="true">
                @for ($day = 1; $day <= $length; $day++)
                    <span class="flex-1 rounded-[1px] {{ $day <= $elapsed ? 'bg-rail-ink/70' : 'bg-white/10' }}"></span>
                @endfor
            </span>

            <span class="mt-2 block font-mono text-[0.6875rem] tracking-tight">
                @if ($elapsed === 0)
                    Starts {{ $selected->start_date->format('j M') }}
                @elseif ($elapsed < $length)
                    Day {{ $elapsed }} of {{ $length }}
                @else
                    Closed · {{ $length }} days
                @endif
            </span>
        @else
            <span class="mt-1 block text-sm text-rail-ink">—</span>
        @endif
    </button>

    @if ($this->periods->isNotEmpty())
        <div
            x-show="open"
            x-cloak
            x-transition.opacity.duration.100ms
            class="border-t border-rail-line px-2 py-2"
        >
            @foreach ($this->periods as $period)
                <button
                    type="button"
                    wire:click="switchPeriod({{ $period->id }})"
                    class="flex w-full items-center justify-between rounded-md px-3 py-1.5 text-left text-[0.8125rem] transition-colors hover:bg-white/5 hover:text-rail-ink"
                >
                    <span>{{ $period->start_date->format('j M') }} – {{ $period->end_date->format('j M Y') }}</span>
                    @if ($period->is_current)
                        <span class="text-[0.6875rem] text-rail-ink">Current</span>
                    @endif
                </button>
            @endforeach
        </div>
    @endif
</div>
