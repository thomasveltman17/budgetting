<div>
    <p class="text-xs text-gray-500 mb-3">
        Period: <span class="font-semibold text-gray-700">{{ $period->start_date->format('j M') }} – {{ $period->end_date->format('j M Y') }}</span>
    </p>

    @if ($transactionalCategories->isEmpty())
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-8 text-center">
            <p class="text-sm text-gray-500">No active transactional categories.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            @foreach ($transactionalCategories as $index => $category)
                @php
                    $isLast = $index === $transactionalCategories->count() - 1;
                    $key = (string) $category->id;
                    $spent = $categorySpend[$category->id] ?? 0;
                    $target = $targetAmounts[$key] ?? '';
                    $percentage = ($target !== '' && (float) $target > 0)
                        ? round(($spent / (float) $target) * 100)
                        : null;
                    $barColor = match (true) {
                        $percentage === null  => 'bg-gray-200',
                        $percentage > 100     => 'bg-red-500',
                        $percentage > 80      => 'bg-orange-400',
                        default               => 'bg-blue-500',
                    };
                @endphp

                <div class="px-5 py-4 {{ $isLast ? '' : 'border-b border-gray-50' }}">
                    <div class="flex items-center gap-4">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $category->color }}"></span>

                        <span class="flex-1 text-sm font-semibold text-gray-800 min-w-0 truncate">{{ $category->name }}</span>

                        <div class="flex items-center gap-1 text-xs text-gray-500 shrink-0">
                            <span class="text-gray-400">Spent</span>
                            <span class="font-semibold text-gray-700 tabular-nums">€&thinsp;{{ number_format($spent, 2, ',', '.') }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="text-xs text-gray-400 select-none">€</span>
                            <input
                                type="number"
                                wire:model="targetAmounts.{{ $key }}"
                                step="0.01"
                                min="0"
                                placeholder="No target"
                                class="w-28 border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm text-gray-900 tabular-nums focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                            >
                        </div>

                        @if ($percentage !== null)
                            <span class="text-xs font-semibold tabular-nums w-10 text-right shrink-0 {{ $percentage > 100 ? 'text-red-500' : 'text-gray-500' }}">
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
                            class="shrink-0 px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                        >Save</button>
                    </div>

                    @error("targetAmounts.{$key}")
                        <p class="mt-1.5 ml-6 text-xs text-red-500">{{ $message }}</p>
                    @enderror

                    {{-- Mini progress bar --}}
                    @if ($target !== '')
                        <div class="mt-2.5 ml-6 h-1 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full {{ $barColor }} rounded-full transition-all duration-300"
                                 style="width: {{ min(100, $percentage ?? 0) }}%"></div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
