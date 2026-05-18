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

<div x-data="{ open: false }" style="border-bottom: 1px solid var(--c-border);">
    <button
        @click="open = !open"
        class="th-hover-row w-full flex items-center justify-between gap-2 px-4 py-3.5 text-left transition-colors"
    >
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-widest mb-1" style="color: var(--c-text-3); letter-spacing: 0.1em;">Period</p>
            <p class="text-sm font-semibold truncate" style="color: var(--c-text-1);">
                @if ($this->selectedPeriod)
                    {{ $this->selectedPeriod->start_date->format('j M') }} – {{ $this->selectedPeriod->end_date->format('j M') }}
                    @if ($this->selectedPeriod->is_current)
                        <span class="text-xs font-normal ml-1" style="color: var(--c-brand);">now</span>
                    @endif
                @else
                    —
                @endif
            </p>
        </div>
        <svg class="w-3.5 h-3.5 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': open }" style="color: var(--c-text-3);" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
        </svg>
    </button>

    @if ($this->periods->isNotEmpty())
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            @click.outside="open = false"
            class="pb-2 px-2"
        >
            @foreach ($this->periods as $period)
                <button
                    wire:click="switchPeriod({{ $period->id }})"
                    class="th-hover-row w-full text-left text-xs px-3 py-2 rounded-lg transition-all"
                    style="color: var(--c-text-2);"
                >
                    {{ $period->start_date->format('j M') }} – {{ $period->end_date->format('j M') }}
                    @if ($period->is_current)
                        <span class="ml-1" style="color: var(--c-brand);">now</span>
                    @endif
                </button>
            @endforeach
        </div>
    @endif
</div>
