<div>
    <div class="flex items-center justify-between mb-4">
        <p class="text-xs" style="color: var(--c-text-3);">
            Period: <span class="font-semibold" style="color: var(--c-text-2);">{{ $period->start_date->format('j M') }} – {{ $period->end_date->format('j M Y') }}</span>
        </p>
        <button
            wire:click="importFromPreviousPeriod"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-60"
            class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-all"
            style="color: var(--c-brand); background-color: var(--c-brand-subtle, rgba(124,111,247,0.12));"
            onmouseover="this.style.opacity='0.85'"
            onmouseout="this.style.opacity='1'"
        >
            Import from last month
        </button>
    </div>

    @if ($categories->isEmpty())
        <div class="rounded-2xl px-5 py-8 text-center" style="background-color: var(--c-card); border: 1px solid var(--c-border);">
            <p class="text-sm" style="color: var(--c-text-3);">No active categories.</p>
        </div>
    @else
        <div class="rounded-2xl overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border);">
            @foreach ($categories as $index => $category)
                @php
                    $isLast = $index === $categories->count() - 1;
                    $key = (string) $category->id;
                    $spent = $categorySpend[$category->id] ?? 0;
                    $target = $targetAmounts[$key] ?? '';
                    $percentage = ($target !== '' && (float) $target > 0)
                        ? round(($spent / (float) $target) * 100)
                        : null;
                    $isGoalCategory = in_array($category->type, ['savings', 'investment']);
                    $barGradient = match (true) {
                        $percentage === null                  => 'background-color: var(--c-elevated)',
                        $percentage > 100 && $isGoalCategory => 'background: linear-gradient(90deg, var(--c-income), #10B981)',
                        $percentage > 100                    => 'background: linear-gradient(90deg, var(--c-expense), #EF4444)',
                        $percentage > 80 && !$isGoalCategory => 'background: linear-gradient(90deg, var(--c-warn), #F59E0B)',
                        default                              => 'background: linear-gradient(90deg, var(--c-brand), #6366F1)',
                    };
                    $percentageColorVar = match (true) {
                        $percentage === null                  => 'var(--c-text-3)',
                        $percentage > 100 && $isGoalCategory => 'var(--c-income)',
                        $percentage > 100                    => 'var(--c-expense)',
                        $percentage > 80 && !$isGoalCategory => 'var(--c-warn)',
                        default                              => 'var(--c-brand)',
                    };
                @endphp

                <div class="px-5 py-4" style="{{ $isLast ? '' : 'border-bottom: 1px solid var(--c-border-subtle);' }}">
                    <div class="flex items-center gap-4">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $category->color }}"></span>

                        <span class="flex-1 text-sm font-semibold min-w-0 truncate" style="color: var(--c-text-1);">{{ $category->name }}</span>

                        <div class="flex items-center gap-1 text-xs shrink-0">
                            <span style="color: var(--c-text-3);">Spent</span>
                            <span class="font-semibold tabular-nums" style="color: var(--c-text-2);">€&thinsp;{{ number_format($spent, 2, ',', '.') }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="text-xs select-none" style="color: var(--c-text-3);">€</span>
                            <input
                                type="number"
                                wire:model="targetAmounts.{{ $key }}"
                                step="0.01"
                                min="0"
                                placeholder="No target"
                                class="th-input w-28 rounded-lg px-2.5 py-1.5 text-sm tabular-nums"
                            >
                        </div>

                        @if ($percentage !== null)
                            <span class="text-xs font-semibold tabular-nums w-10 text-right shrink-0" style="color: {{ $percentageColorVar }};">
                                {{ $percentage }}%
                            </span>
                        @else
                            <span class="w-10 shrink-0"></span>
                        @endif

                        <button
                            wire:click="saveTarget({{ $category->id }})"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-70"
                            wire:target="saveTarget({{ $category->id }})"
                            class="shrink-0 px-3 py-1.5 text-xs font-semibold text-white rounded-lg transition-all"
                            style="background: linear-gradient(135deg, #7C6FF7, #5B4FD4);"
                        >Save</button>
                    </div>

                    @error("targetAmounts.{$key}")
                        <p class="mt-1.5 ml-6 text-xs" style="color: var(--c-expense);">{{ $message }}</p>
                    @enderror

                    @if ($target !== '')
                        <div class="mt-2.5 ml-6 h-1.5 rounded-full overflow-hidden" style="background-color: var(--c-elevated);">
                            <div class="h-full rounded-full transition-all duration-300" style="{{ $barGradient }}; width: {{ min(100, $percentage ?? 0) }}%"></div>
                        </div>
                    @endif
                </div>
            @endforeach

            @php
                $totalSpent = array_sum(array_values($categorySpend));
                $totalBudget = collect($targetAmounts)->filter(fn ($v) => $v !== '')->sum(fn ($v) => (float) $v);
                $totalPercentage = ($totalBudget > 0) ? round(($totalSpent / $totalBudget) * 100) : null;
            @endphp
            <div class="px-5 py-3 flex items-center gap-4" style="border-top: 1px solid var(--c-border); background-color: var(--c-footer);">
                <span class="w-2.5 shrink-0"></span>
                <span class="flex-1 text-xs font-bold uppercase tracking-widest" style="color: var(--c-text-3);">Total</span>
                <div class="flex items-center gap-1 text-xs shrink-0">
                    <span style="color: var(--c-text-3);">Spent</span>
                    <span class="font-bold tabular-nums" style="color: var(--c-text-1);">€&thinsp;{{ number_format($totalSpent, 2, ',', '.') }}</span>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <span class="text-xs select-none" style="color: var(--c-text-3);">€</span>
                    <span class="w-28 px-2.5 py-1.5 text-sm font-bold tabular-nums" style="color: var(--c-text-1);">{{ $totalBudget > 0 ? number_format($totalBudget, 2, ',', '.') : '—' }}</span>
                </div>
                @if ($totalPercentage !== null)
                    <span class="text-xs font-bold tabular-nums w-10 text-right shrink-0" style="color: var(--c-brand);">{{ $totalPercentage }}%</span>
                @else
                    <span class="w-10 shrink-0"></span>
                @endif
                <span class="shrink-0 w-[52px]"></span>
            </div>
        </div>
    @endif
</div>
