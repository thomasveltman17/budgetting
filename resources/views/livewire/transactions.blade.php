<div class="flex flex-col min-h-full" x-data="{ selectedIds: [] }">

    <p class="text-xs mb-4 px-6 pt-4" style="color: var(--c-text-3);">
        Period: <span class="font-semibold" style="color: var(--c-text-2);">{{ $period->start_date->format('j M') }} – {{ $period->end_date->format('j M Y') }}</span>
    </p>

    {{-- ── Filter Bar ──────────────────────────────────────────────────── --}}
    <div class="sticky top-0 z-20" style="background-color: var(--c-card); border-bottom: 1px solid var(--c-border);">

        {{-- Row 1: Search --}}
        <div class="px-6 py-3" style="border-bottom: 1px solid var(--c-border-subtle);">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color: var(--c-text-3);" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by description or notes…"
                    class="th-input w-full pl-9 pr-9 py-2 text-sm rounded-lg"
                >
                @if ($search !== '')
                    <button wire:click="$set('search', '')" class="th-btn th-btn-ghost absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        {{-- Row 2: Filters --}}
        <div class="px-6 py-2.5 flex flex-wrap items-center gap-x-4 gap-y-2">

            {{-- Account filter --}}
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-widest" style="color: var(--c-text-3);">Account</span>
                <select wire:model.live="filterAccount" class="th-select text-sm rounded-lg px-3 py-1.5">
                    <option value="">All</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="h-5 w-px hidden sm:block" style="background-color: var(--c-border);"></div>

            {{-- Category filter --}}
            <div class="flex items-center gap-2" x-bind:class="$wire.uncategorizedOnly ? 'opacity-40 pointer-events-none' : ''">
                <span class="text-xs font-semibold uppercase tracking-widest" style="color: var(--c-text-3);">Category</span>
                <select wire:model.live="filterCategory" class="th-select text-sm rounded-lg px-3 py-1.5">
                    <option value="">All</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="h-5 w-px hidden sm:block" style="background-color: var(--c-border);"></div>

            {{-- Type filter --}}
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-widest" style="color: var(--c-text-3);">Type</span>
                <select wire:model.live="filterType" class="th-select text-sm rounded-lg px-3 py-1.5">
                    <option value="">All</option>
                    <option value="expense">Expenses</option>
                    <option value="income">Income</option>
                </select>
            </div>

            <div class="h-5 w-px hidden sm:block" style="background-color: var(--c-border);"></div>

            {{-- Uncategorized only toggle --}}
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <div class="relative">
                    <input type="checkbox" wire:model.live="uncategorizedOnly" class="sr-only peer">
                    <div class="w-8 h-4 rounded-full transition-colors peer-checked:!bg-amber-400" style="background-color: var(--c-elevated);"></div>
                    <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-4"></div>
                </div>
                <span class="text-sm font-medium" style="color: var(--c-text-2);">Uncategorized</span>
            </label>

            {{-- Pending return toggle --}}
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <div class="relative">
                    <input type="checkbox" wire:model.live="filterPendingReturn" class="sr-only peer">
                    <div class="w-8 h-4 rounded-full transition-colors peer-checked:!bg-amber-500" style="background-color: var(--c-elevated);"></div>
                    <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-4"></div>
                </div>
                <span class="text-sm font-medium" style="color: var(--c-text-2);">Pending return</span>
            </label>

            {{-- Clear filters --}}
            @if ($hasActiveFilters)
                <button wire:click="clearFilters" class="th-link ml-auto flex items-center gap-1.5 text-xs font-semibold">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                    Clear filters
                </button>
            @endif

            {{-- Uncategorized warning badge --}}
            @if ($uncategorizedCount > 0 && !$hasActiveFilters)
                <div class="ml-auto flex items-center gap-1.5 px-3 py-1.5 rounded-lg" style="background-color: var(--c-warn-bg); border: 1px solid var(--c-warn-border);">
                    <svg class="w-3.5 h-3.5 shrink-0" style="color: var(--c-warn);" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <span class="text-xs font-semibold" style="color: var(--c-warn-text);">
                        {{ $uncategorizedCount }} {{ Str::plural('transaction', $uncategorizedCount) }} uncategorized
                    </span>
                </div>
            @endif

        </div>
    </div>

    {{-- ── Transaction List ─────────────────────────────────────────────── --}}
    <div class="flex-1 px-6 py-6 pb-32">

        @if ($transactions->isEmpty())
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5" style="background-color: var(--c-elevated); border: 1px solid var(--c-border);">
                    <svg class="w-7 h-7" style="color: var(--c-text-3);" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75" />
                    </svg>
                </div>
                <p class="text-base font-semibold" style="color: var(--c-text-1);">No transactions found</p>
                <p class="text-sm mt-1" style="color: var(--c-text-3);">
                    @if ($filterAccount || $filterCategory || $uncategorizedOnly)
                        Try adjusting your filters
                    @else
                        Add your first transaction using the button below
                    @endif
                </p>
            </div>

        @else

            @foreach ($transactions as $dateStr => $group)

                {{-- Date group header --}}
                <div class="flex items-center gap-3 mt-7 mb-2 first:mt-0">
                    <span class="text-xs font-bold uppercase tracking-widest whitespace-nowrap" style="color: var(--c-text-3);">
                        {{ \Carbon\Carbon::parse($dateStr)->format('D, j M Y') }}
                    </span>
                    <div class="flex-1 h-px" style="background-color: var(--c-border);"></div>
                    <span class="text-xs tabular-nums whitespace-nowrap" style="color: var(--c-text-3);">
                        {{ $group->count() }} {{ Str::plural('transaction', $group->count()) }}
                    </span>
                </div>

                {{-- Transaction rows card --}}
                <div class="rounded-2xl overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border);">
                    @foreach ($group as $index => $transaction)
                        @php
                            $isNegative = $transaction->amount < 0;
                            $accountBadgeStyle = match ($transaction->account->name) {
                                'rabobank' => 'background-color: var(--c-rabo-bg); color: var(--c-rabo-text);',
                                'revolut'  => 'background-color: var(--c-revolut-bg); color: var(--c-revolut-text);',
                                'amex'     => 'background-color: var(--c-amex-bg); color: var(--c-amex-text);',
                                default    => 'background-color: var(--c-hover); color: var(--c-text-2);',
                            };
                        @endphp

                        <div
                            class="th-hover-row flex items-center gap-4 px-4 py-3.5 group"
                            style="{{ $index < $group->count() - 1 ? 'border-bottom: 1px solid var(--c-border-subtle);' : '' }}"
                        >

                            {{-- Checkbox --}}
                            <div class="shrink-0">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes({{ $transaction->id }})"
                                    @change="$event.target.checked
                                        ? selectedIds.push({{ $transaction->id }})
                                        : selectedIds = selectedIds.filter(id => id !== {{ $transaction->id }})"
                                    class="w-4 h-4 rounded cursor-pointer"
                                    style="accent-color: var(--c-brand);"
                                >
                            </div>

                            {{-- Description + notes --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 min-w-0">
                                    <p class="text-sm font-medium truncate leading-tight" style="color: var(--c-text-1);">
                                        {{ $transaction->description }}
                                    </p>
                                    @if ($transaction->is_pending_return)
                                        <span class="shrink-0 inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full" style="background-color: var(--c-pending-bg); color: var(--c-pending-text); border: 1px solid var(--c-pending-border);">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            Pending return
                                        </span>
                                    @endif
                                </div>
                                @if ($transaction->notes)
                                    <p class="text-xs truncate mt-0.5 leading-tight" style="color: var(--c-text-3);">
                                        {{ $transaction->notes }}
                                    </p>
                                @endif
                            </div>

                            {{-- Category inline select --}}
                            <div class="shrink-0">
                                <select
                                    @change="$wire.updateCategory({{ $transaction->id }}, $event.target.value)"
                                    class="text-xs font-semibold rounded-full py-1 pl-3 pr-7 cursor-pointer transition-colors"
                                    style="appearance: auto; border: none; outline: none; {{ $transaction->category_id
                                        ? 'background-color: var(--c-elevated); color: var(--c-text-2);'
                                        : 'background-color: var(--c-warn-bg); color: var(--c-warn-text);' }}"
                                >
                                    <option value="0" {{ ! $transaction->category_id ? 'selected' : '' }}>Uncategorized</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" {{ $transaction->category_id == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Account badge --}}
                            <span class="shrink-0 text-xs font-semibold rounded-full px-2.5 py-1 whitespace-nowrap" style="{{ $accountBadgeStyle }}">
                                {{ $transaction->account->label }}
                            </span>

                            {{-- Amount --}}
                            <div class="shrink-0 w-32 text-right {{ $transaction->is_pending_return ? 'opacity-40' : '' }}">
                                <span class="text-sm font-bold tabular-nums" style="color: var({{ $isNegative ? '--c-expense' : '--c-income' }});">
                                    {{ $isNegative ? '−' : '+' }}&thinsp;€&thinsp;{{ number_format(abs($transaction->amount), 2, ',', '.') }}
                                </span>
                                @if ($transaction->repayments->isNotEmpty())
                                    @php $net = (float) $transaction->amount + $transaction->repayments->sum('amount'); @endphp
                                    <p class="text-xs tabular-nums mt-0.5" style="color: var(--c-text-3);">
                                        net {{ $net < 0 ? '−' : '+' }}&thinsp;€&thinsp;{{ number_format(abs($net), 2, ',', '.') }}
                                    </p>
                                @endif
                            </div>

                            {{-- Link repayments button --}}
                            @if ($transaction->repayments->isNotEmpty())
                                <button wire:click="openLinkModal({{ $transaction->id }})" title="Link repayments"
                                        class="th-btn shrink-0" style="color: var(--c-income); background-color: var(--c-income-bg);">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                    </svg>
                                </button>
                            @else
                                <button wire:click="openLinkModal({{ $transaction->id }})" title="Link repayments"
                                        class="th-btn th-btn-success opacity-0 group-hover:opacity-100 shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                    </svg>
                                </button>
                            @endif

                            {{-- Pending return toggle --}}
                            @if ($transaction->is_pending_return)
                                <button wire:click="togglePendingReturn({{ $transaction->id }})" title="Clear pending return flag"
                                        class="th-btn shrink-0" style="color: var(--c-warn); background-color: var(--c-pending-bg);">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </button>
                            @else
                                <button wire:click="togglePendingReturn({{ $transaction->id }})" title="Mark as pending return"
                                        class="th-btn th-btn-warn opacity-0 group-hover:opacity-100 shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </button>
                            @endif

                            {{-- Edit button --}}
                            <button wire:click="startEdit({{ $transaction->id }})" title="Edit"
                                    class="th-btn th-btn-brand opacity-0 group-hover:opacity-100 shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                </svg>
                            </button>

                            {{-- Delete with inline confirmation --}}
                            <div x-data="{ confirming: false }" class="shrink-0 w-20 flex items-center justify-end gap-1">
                                <button x-show="!confirming" x-on:click="confirming = true" title="Delete"
                                        class="th-btn th-btn-danger opacity-0 group-hover:opacity-100">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                <div x-show="confirming" x-cloak class="flex items-center gap-1">
                                    <button wire:click="deleteTransaction({{ $transaction->id }})"
                                            class="px-2 py-0.5 text-xs font-semibold text-white rounded-md transition-colors"
                                            style="background-color: var(--c-expense);">Yes</button>
                                    <button x-on:click="confirming = false"
                                            class="px-2 py-0.5 text-xs font-medium rounded-md transition-colors"
                                            style="color: var(--c-text-2); background-color: var(--c-elevated);">No</button>
                                </div>
                            </div>

                        </div>

                        {{-- Nested repayment rows --}}
                        @foreach ($transaction->repayments as $repayment)
                            @php
                                $repaymentBadgeStyle = match ($repayment->account->name) {
                                    'rabobank' => 'background-color: var(--c-rabo-bg); color: var(--c-rabo-text);',
                                    'revolut'  => 'background-color: var(--c-revolut-bg); color: var(--c-revolut-text);',
                                    'amex'     => 'background-color: var(--c-amex-bg); color: var(--c-amex-text);',
                                    default    => 'background-color: var(--c-hover); color: var(--c-text-2);',
                                };
                            @endphp
                            <div class="flex items-center gap-4 pl-9 pr-4 py-2.5" style="background-color: var(--c-elevated); border-top: 1px solid var(--c-success-border);">
                                <svg class="w-3 h-3 shrink-0" style="color: var(--c-income);" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4 4 8 8-8 8" />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs truncate leading-tight" style="color: var(--c-text-2);">{{ $repayment->description }}</p>
                                    @if ($repayment->notes)
                                        <p class="text-xs truncate mt-0.5" style="color: var(--c-text-3);">{{ $repayment->notes }}</p>
                                    @endif
                                </div>
                                <span class="shrink-0 text-xs font-semibold rounded-full px-2.5 py-1 whitespace-nowrap" style="{{ $repaymentBadgeStyle }}">
                                    {{ $repayment->account->label }}
                                </span>
                                <span class="shrink-0 w-32 text-right text-xs font-bold tabular-nums" style="color: var(--c-income);">
                                    +&thinsp;€&thinsp;{{ number_format(abs($repayment->amount), 2, ',', '.') }}
                                </span>
                                <button wire:click="unlinkRepayment({{ $repayment->id }})" title="Unlink repayment"
                                        class="th-btn th-btn-danger shrink-0">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.181 8.68a4.503 4.503 0 0 1 1.903 6.405m-9.768-3.782L3.56 8.836a4.5 4.5 0 0 1 5.48-6.523m0 0L3.56 8.836M16.5 11.25 18 12.75m-12 0L7.5 15.75m12.75-3 1.5 1.5M3 12l1.5 1.5M21 12l-1.5-1.5M3 12l1.5-1.5" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach

                    @endforeach
                </div>

            @endforeach

        @endif
    </div>

    {{-- ── Bulk Action Bar ──────────────────────────────────────────────── --}}
    <div
        x-show="selectedIds.length > 0"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-24 right-6 z-40 flex items-center gap-3 rounded-2xl shadow-2xl px-4 py-3"
        style="background-color: var(--c-elevated); border: 1px solid var(--c-border-medium);"
    >
        <span class="text-sm font-medium" style="color: var(--c-text-2);" x-text="`${selectedIds.length} selected`"></span>
        <button
            @click="$wire.deleteSelected(selectedIds).then(() => { selectedIds = [] })"
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold text-white rounded-lg transition-colors"
            style="background-color: var(--c-expense);">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
            Delete selected
        </button>
        <button @click="selectedIds = []" class="th-link text-sm font-medium">Clear</button>
    </div>

    {{-- ── Floating Action Buttons ──────────────────────────────────────── --}}
    <div class="fixed bottom-6 right-6 z-40 flex items-center gap-2">
        <button
            wire:click="openImportModal"
            class="flex items-center gap-2 px-4 py-3 rounded-full text-sm font-semibold transition-all"
            style="background-color: var(--c-elevated); border: 1px solid var(--c-border-medium); color: var(--c-text-2);"
            onmouseover="this.style.opacity='0.85'"
            onmouseout="this.style.opacity='1'"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
            </svg>
            Import
        </button>
        <button
            wire:click="openModal"
            class="flex items-center gap-2 px-5 py-3 rounded-full text-sm font-semibold text-white transition-all"
            style="background: linear-gradient(135deg, #7C6FF7, #5B4FD4); box-shadow: 0 8px 24px var(--c-brand-glow);"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add transaction
        </button>
    </div>

    {{-- ── Import Modal ─────────────────────────────────────────────────── --}}
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.closeImportModal()">
            <div class="absolute inset-0 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.6);" wire:click="closeImportModal"></div>
            <div class="relative w-full max-w-md rounded-2xl shadow-2xl overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border-medium);">

                <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid var(--c-border);">
                    <h2 class="text-base font-bold" style="color: var(--c-text-1);">Import transactions</h2>
                    <button wire:click="closeImportModal" class="th-btn th-btn-ghost">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($importResult !== null)
                    <div class="px-6 py-6 space-y-4">
                        <div class="flex items-center gap-3 p-4 rounded-xl" style="background-color: var(--c-success-bg); border: 1px solid var(--c-success-border);">
                            <svg class="w-5 h-5 shrink-0" style="color: var(--c-success-text);" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <div>
                                <p class="text-sm font-bold" style="color: var(--c-success-text);">{{ $importResult['imported'] }} transaction{{ $importResult['imported'] !== 1 ? 's' : '' }} imported</p>
                                @if ($importResult['skipped'] > 0)
                                    <p class="text-xs mt-0.5" style="color: var(--c-success-text); opacity: 0.7;">{{ $importResult['skipped'] }} duplicate{{ $importResult['skipped'] !== 1 ? 's' : '' }} skipped</p>
                                @endif
                            </div>
                        </div>
                        @if (count($importResult['errors']) > 0)
                            <div class="p-4 rounded-xl" style="background-color: var(--c-error-bg); border: 1px solid var(--c-error-border);">
                                <p class="text-xs font-bold mb-2" style="color: var(--c-error-text);">{{ count($importResult['errors']) }} error{{ count($importResult['errors']) !== 1 ? 's' : '' }}</p>
                                <ul class="space-y-1">
                                    @foreach (array_slice($importResult['errors'], 0, 5) as $error)
                                        <li class="text-xs" style="color: var(--c-error-text); opacity: 0.8;">{{ $error }}</li>
                                    @endforeach
                                    @if (count($importResult['errors']) > 5)
                                        <li class="text-xs italic" style="color: var(--c-error-text); opacity: 0.6;">… and {{ count($importResult['errors']) - 5 }} more</li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center justify-end gap-3 px-6 py-4" style="background-color: var(--c-footer); border-top: 1px solid var(--c-border);">
                        <button wire:click="closeImportModal" class="px-5 py-2 text-sm font-semibold text-white rounded-lg" style="background: linear-gradient(135deg, #7C6FF7, #5B4FD4);">Done</button>
                    </div>
                @else
                    <div class="px-6 py-5 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Bank</label>
                            <select wire:model.live="importBank" class="th-select w-full rounded-lg px-3 py-2 text-sm">
                                <option value="">Select bank…</option>
                                <option value="rabobank">Rabobank (CSV, semicolon-delimited)</option>
                                <option value="revolut">Revolut (CSV)</option>
                                <option value="amex">American Express (CSV)</option>
                            </select>
                            @error('importBank') <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Account</label>
                            <select wire:model="importAccountId" class="th-select w-full rounded-lg px-3 py-2 text-sm">
                                <option value="">Select account…</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->label }}</option>
                                @endforeach
                            </select>
                            @error('importAccountId') <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">File</label>
                            <input type="file" wire:model="importFile" accept=".csv,.txt,.xlsx"
                                   class="th-input w-full rounded-lg text-sm" style="padding: 0.5rem 0.75rem;">
                            <p class="mt-1 text-xs" style="color: var(--c-text-3);">Accepts .csv and .xlsx files</p>
                            @error('importFile') <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 px-6 py-4" style="background-color: var(--c-footer); border-top: 1px solid var(--c-border);">
                        <button wire:click="closeImportModal" class="px-4 py-2 text-sm font-medium th-link">Cancel</button>
                        <button wire:click="runImport" wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed"
                                class="px-5 py-2 text-sm font-semibold text-white rounded-lg" style="background: linear-gradient(135deg, #7C6FF7, #5B4FD4);">
                            <span wire:loading.remove wire:target="runImport">Import</span>
                            <span wire:loading wire:target="runImport">Importing…</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ── Add Transaction Modal ────────────────────────────────────────── --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.closeModal()">
            <div class="absolute inset-0 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.6);" wire:click="closeModal"></div>
            <div class="relative w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border-medium);">

                <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid var(--c-border);">
                    <div>
                        <h2 class="text-base font-bold" style="color: var(--c-text-1);">Add transaction</h2>
                        <p class="text-xs mt-0.5" style="color: var(--c-text-3);">{{ app(\App\Services\PeriodService::class)->formatLabel($period) }}</p>
                    </div>
                    <button wire:click="closeModal" class="th-btn th-btn-ghost">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Date</label>
                            <input type="date" wire:model="newDate" class="th-input w-full rounded-lg px-3 py-2 text-sm">
                            @error('newDate') <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Amount</label>
                            <div class="flex">
                                <span class="flex items-center px-3 text-sm font-medium rounded-l-lg select-none" style="background-color: var(--c-deep); border: 1px solid var(--c-border-medium); border-right: none; color: var(--c-text-3);">€</span>
                                <input type="number" wire:model="newAmount" step="0.01" placeholder="-45.00"
                                       class="th-input flex-1 min-w-0 rounded-r-lg px-3 py-2 text-sm" style="border-left: none; border-radius: 0 0.5rem 0.5rem 0;">
                            </div>
                            <p class="mt-1 text-xs" style="color: var(--c-text-3);">Negative = expense, positive = income</p>
                            @error('newAmount') <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Description</label>
                        <input type="text" wire:model="newDescription" placeholder="e.g. Albert Heijn, Netflix, Salary..." autofocus
                               class="th-input w-full rounded-lg px-3 py-2 text-sm">
                        @error('newDescription') <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Account</label>
                            <select wire:model="newAccountId" class="th-select w-full rounded-lg px-3 py-2 text-sm">
                                <option value="">Select account…</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->label }}</option>
                                @endforeach
                            </select>
                            @error('newAccountId') <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Category <span class="font-normal normal-case">(optional)</span></label>
                            <select wire:model="newCategoryId" class="th-select w-full rounded-lg px-3 py-2 text-sm">
                                <option value="">Uncategorized</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Notes <span class="font-normal normal-case">(optional)</span></label>
                        <textarea wire:model="newNotes" rows="2" placeholder="Any additional details…"
                                  class="th-input w-full rounded-lg px-3 py-2 text-sm resize-none"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4" style="background-color: var(--c-footer); border-top: 1px solid var(--c-border);">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm font-medium th-link">Cancel</button>
                    <button wire:click="save" wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed"
                            class="px-5 py-2 text-sm font-semibold text-white rounded-lg" style="background: linear-gradient(135deg, #7C6FF7, #5B4FD4);">
                        <span wire:loading.remove wire:target="save">Save transaction</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Edit Transaction Modal ──────────────────────────────────────── --}}
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.cancelEdit()">
            <div class="absolute inset-0 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.6);" wire:click="cancelEdit"></div>
            <div class="relative w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border-medium);">

                <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid var(--c-border);">
                    <h2 class="text-base font-bold" style="color: var(--c-text-1);">Edit transaction</h2>
                    <button wire:click="cancelEdit" class="th-btn th-btn-ghost">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Date</label>
                            <input type="date" wire:model="editDate" class="th-input w-full rounded-lg px-3 py-2 text-sm">
                            @error('editDate') <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Amount</label>
                            <div class="flex">
                                <span class="flex items-center px-3 text-sm font-medium rounded-l-lg select-none" style="background-color: var(--c-deep); border: 1px solid var(--c-border-medium); border-right: none; color: var(--c-text-3);">€</span>
                                <input type="number" wire:model="editAmount" step="0.01" placeholder="-45.00"
                                       class="th-input flex-1 min-w-0 rounded-r-lg px-3 py-2 text-sm" style="border-left: none; border-radius: 0 0.5rem 0.5rem 0;">
                            </div>
                            <p class="mt-1 text-xs" style="color: var(--c-text-3);">Negative = expense, positive = income</p>
                            @error('editAmount') <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Description</label>
                        <input type="text" wire:model="editDescription" class="th-input w-full rounded-lg px-3 py-2 text-sm">
                        @error('editDescription') <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Account</label>
                            <select wire:model="editAccountId" class="th-select w-full rounded-lg px-3 py-2 text-sm">
                                <option value="">Select account…</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->label }}</option>
                                @endforeach
                            </select>
                            @error('editAccountId') <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Category <span class="font-normal normal-case">(optional)</span></label>
                            <select wire:model="editCategoryId" class="th-select w-full rounded-lg px-3 py-2 text-sm">
                                <option value="">Uncategorized</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5" style="color: var(--c-text-3);">Notes <span class="font-normal normal-case">(optional)</span></label>
                        <textarea wire:model="editNotes" rows="2" placeholder="Any additional details…"
                                  class="th-input w-full rounded-lg px-3 py-2 text-sm resize-none"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4" style="background-color: var(--c-footer); border-top: 1px solid var(--c-border);">
                    <button wire:click="cancelEdit" class="px-4 py-2 text-sm font-medium th-link">Cancel</button>
                    <button wire:click="saveEdit" wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed"
                            class="px-5 py-2 text-sm font-semibold text-white rounded-lg" style="background: linear-gradient(135deg, #7C6FF7, #5B4FD4);">
                        <span wire:loading.remove wire:target="saveEdit">Save changes</span>
                        <span wire:loading wire:target="saveEdit">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Link Repayments Modal ───────────────────────────────────────── --}}
    @if ($showLinkModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.closeLinkModal()">
            <div class="absolute inset-0 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.6);" wire:click="closeLinkModal"></div>
            <div class="relative w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[80vh]" style="background-color: var(--c-card); border: 1px solid var(--c-border-medium);">

                <div class="flex items-center justify-between px-6 py-4 shrink-0" style="border-bottom: 1px solid var(--c-border);">
                    <div>
                        <h2 class="text-base font-bold" style="color: var(--c-text-1);">Link repayments</h2>
                        <p class="text-xs mt-0.5" style="color: var(--c-text-3);">Select incoming payments that offset this expense</p>
                    </div>
                    <button wire:click="closeLinkModal" class="th-btn th-btn-ghost">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="overflow-y-auto flex-1">
                    @if ($linkableTransactions->isEmpty())
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <p class="text-sm font-semibold" style="color: var(--c-text-2);">No linkable transactions found</p>
                            <p class="text-xs mt-1" style="color: var(--c-text-3);">Import or add positive/incoming transactions first</p>
                        </div>
                    @else
                        <div>
                            @foreach ($linkableTransactions as $linkable)
                                @php
                                    $linkableBadgeStyle = match ($linkable->account->name) {
                                        'rabobank' => 'background-color: var(--c-rabo-bg); color: var(--c-rabo-text);',
                                        'revolut'  => 'background-color: var(--c-revolut-bg); color: var(--c-revolut-text);',
                                        'amex'     => 'background-color: var(--c-amex-bg); color: var(--c-amex-text);',
                                        default    => 'background-color: var(--c-hover); color: var(--c-text-2);',
                                    };
                                @endphp
                                <div class="th-hover-row flex items-center gap-4 px-6 py-3" style="border-bottom: 1px solid var(--c-border-subtle);">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium truncate" style="color: var(--c-text-1);">{{ $linkable->description }}</p>
                                        <p class="text-xs mt-0.5" style="color: var(--c-text-3);">{{ $linkable->date->format('j M Y') }}</p>
                                    </div>
                                    <span class="shrink-0 text-xs font-semibold rounded-full px-2.5 py-1 whitespace-nowrap" style="{{ $linkableBadgeStyle }}">
                                        {{ $linkable->account->label }}
                                    </span>
                                    <span class="shrink-0 w-24 text-right text-sm font-bold tabular-nums" style="color: var(--c-income);">
                                        +&thinsp;€&thinsp;{{ number_format(abs($linkable->amount), 2, ',', '.') }}
                                    </span>
                                    <button wire:click="linkRepayment({{ $linkable->id }})"
                                            class="shrink-0 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors"
                                            style="background-color: var(--c-income-bg); color: var(--c-income); border: 1px solid var(--c-success-border);"
                                            onmouseover="this.style.opacity='0.8'"
                                            onmouseout="this.style.opacity='1'">
                                        Link
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="px-6 py-4 shrink-0" style="background-color: var(--c-footer); border-top: 1px solid var(--c-border);">
                    <button wire:click="closeLinkModal" class="px-4 py-2 text-sm font-medium th-link">Done</button>
                </div>
            </div>
        </div>
    @endif

</div>
