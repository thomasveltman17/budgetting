<div class="rounded-xl border border-line bg-surface">
    <div class="flex items-baseline justify-between gap-4 border-b border-line px-4 py-2.5 text-xs text-ink-3">
        <span>{{ $period->start_date->format('j M') }} – {{ $period->end_date->format('j M Y') }}</span>
        <span class="hidden sm:inline">Press Enter or Save to store a target</span>
    </div>

    @if ($categories->isEmpty())
        <p class="px-4 py-8 text-center text-sm text-ink-3">There are no active categories yet.</p>
    @else
        <ul class="divide-y divide-line">
            @foreach ($categories as $category)
                @php
                    $key = (string) $category->id;
                    $spent = $categorySpend[$category->id] ?? 0;
                    $target = $targetAmounts[$key] ?? '';
                    $percentage = ($target !== '' && (float) $target > 0)
                        ? round(($spent / (float) $target) * 100)
                        : null;
                    $isGoalCategory = in_array($category->type, ['savings', 'investment']);
                    $isOver = $percentage !== null && $percentage > 100;
                    $fillClass = match (true) {
                        $isOver && $isGoalCategory => 'bg-credit',
                        $isOver => 'bg-over',
                        $percentage !== null && $percentage > 80 && ! $isGoalCategory => 'bg-attention',
                        default => 'bg-ink-2',
                    };
                @endphp

                <li class="px-4 py-3">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <span class="flex min-w-0 flex-1 basis-40 items-center gap-2.5">
                            <span class="size-2 shrink-0 rounded-[2px]" style="background-color: {{ $category->color }}"></span>
                            <span class="truncate text-sm text-ink">{{ $category->name }}</span>
                        </span>

                        <span class="w-32 text-right text-[0.8125rem] text-ink-3 tabular-nums">spent <x-money :amount="$spent" class="text-ink-2" /></span>

                        <div class="relative w-32">
                            <span class="pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-sm text-ink-3">€</span>
                            <input type="number" step="0.01" min="0"
                                   wire:model="targetAmounts.{{ $key }}"
                                   wire:keydown.enter="saveTarget({{ $category->id }})"
                                   placeholder="No target"
                                   aria-label="Target for {{ $category->name }}"
                                   class="field h-8 pl-6 text-right tabular-nums">
                        </div>

                        <span class="w-10 text-right font-mono text-xs tabular-nums {{ $isOver ? ($isGoalCategory ? 'text-credit' : 'text-over') : 'text-ink-3' }}">
                            {{ $percentage !== null ? $percentage.'%' : '' }}
                        </span>

                        <button type="button"
                                wire:click="saveTarget({{ $category->id }})"
                                wire:loading.attr="disabled"
                                wire:target="saveTarget({{ $category->id }})"
                                class="btn btn-secondary btn-sm">Save</button>
                    </div>

                    <x-field-error name="targetAmounts.{{ $key }}" class="pl-[1.125rem]" />

                    @if ($target !== '')
                        <div class="mt-2.5 ml-[1.125rem] h-1 rounded-full bg-sunken">
                            <div class="h-full rounded-full {{ $fillClass }}" style="width: {{ min(100, $percentage ?? 0) }}%"></div>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
