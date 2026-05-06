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

<div class="border-b border-slate-700/50" x-data="{ open: false }">
    <button
        @click="open = !open"
        class="w-full flex items-center justify-between gap-2 px-4 py-3 text-left hover:bg-slate-800/50 transition-colors"
    >
        <div class="min-w-0">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-0.5">Period</p>
            <p class="text-sm font-semibold text-slate-200 truncate">
                @if ($this->selectedPeriod)
                    {{ $this->selectedPeriod->start_date->format('j M') }} – {{ $this->selectedPeriod->end_date->format('j M') }}
                    @if ($this->selectedPeriod->is_current)
                        <span class="text-xs font-normal text-slate-500 ml-1">current</span>
                    @endif
                @else
                    —
                @endif
            </p>
        </div>
        <svg class="w-4 h-4 text-slate-500 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
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
                    class="w-full text-left text-xs px-3 py-2 rounded-md transition-colors text-slate-400 hover:bg-slate-800 hover:text-slate-200"
                >
                    {{ $period->start_date->format('j M') }} – {{ $period->end_date->format('j M') }}
                    @if ($period->is_current)
                        <span class="ml-1 text-slate-500">current</span>
                    @endif
                </button>
            @endforeach
        </div>
    @endif
</div>
