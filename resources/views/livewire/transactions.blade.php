<div class="flex flex-col min-h-full" x-data="{ selectedIds: [] }">

    {{-- ── Filter Bar ──────────────────────────────────────────────────── --}}
    <div class="sticky top-0 z-20 bg-white border-b border-gray-200 shadow-sm">
        <div class="px-6 py-3 flex flex-wrap items-center gap-x-4 gap-y-2">

            {{-- Account filter --}}
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Account</span>
                <select wire:model.live="filterAccount"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none cursor-pointer">
                    <option value="">All</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="h-5 w-px bg-gray-200 hidden sm:block"></div>

            {{-- Category filter --}}
            <div class="flex items-center gap-2" x-bind:class="$wire.uncategorizedOnly ? 'opacity-40 pointer-events-none' : ''">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Category</span>
                <select wire:model.live="filterCategory"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none cursor-pointer">
                    <option value="">All</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="h-5 w-px bg-gray-200 hidden sm:block"></div>

            {{-- Uncategorized only toggle --}}
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <div class="relative">
                    <input type="checkbox" wire:model.live="uncategorizedOnly" class="sr-only peer">
                    <div class="w-8 h-4 bg-gray-200 rounded-full peer-checked:bg-orange-400 transition-colors"></div>
                    <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-4"></div>
                </div>
                <span class="text-sm text-gray-600 font-medium">Uncategorized only</span>
            </label>

            {{-- Uncategorized warning badge --}}
            @if ($uncategorizedCount > 0)
                <div class="ml-auto flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 border border-orange-200 rounded-lg">
                    <svg class="w-3.5 h-3.5 text-orange-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <span class="text-xs font-semibold text-orange-700">
                        {{ $uncategorizedCount }} {{ Str::plural('transaction', $uncategorizedCount) }} uncategorized
                    </span>
                </div>
            @endif

        </div>
    </div>

    {{-- ── Transaction List ─────────────────────────────────────────────── --}}
    <div class="flex-1 px-6 py-6 pb-32">

        @if ($transactions->isEmpty())

            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-5 shadow-inner">
                    <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75" />
                    </svg>
                </div>
                <p class="text-base font-semibold text-gray-700">No transactions found</p>
                <p class="text-sm text-gray-400 mt-1">
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
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($dateStr)->format('D, j M Y') }}
                    </span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-xs text-gray-400 tabular-nums whitespace-nowrap">
                        {{ $group->count() }} {{ Str::plural('transaction', $group->count()) }}
                    </span>
                </div>

                {{-- Transaction rows card --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    @foreach ($group as $index => $transaction)
                        @php
                            $isNegative = $transaction->amount < 0;
                            $accountBadge = match ($transaction->account->name) {
                                'rabobank' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-100',
                                'revolut'  => 'bg-violet-50 text-violet-700 ring-1 ring-violet-100',
                                'amex'     => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
                                default    => 'bg-gray-100 text-gray-600',
                            };
                        @endphp

                        <div class="flex items-center gap-4 px-4 py-3.5
                            {{ $index < $group->count() - 1 ? 'border-b border-gray-50' : '' }}
                            hover:bg-gray-50/70 transition-colors group">

                            {{-- Checkbox --}}
                            <div class="shrink-0">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes({{ $transaction->id }})"
                                    @change="$event.target.checked
                                        ? selectedIds.push({{ $transaction->id }})
                                        : selectedIds = selectedIds.filter(id => id !== {{ $transaction->id }})"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                >
                            </div>

                            {{-- Description + notes --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate leading-tight">
                                    {{ $transaction->description }}
                                </p>
                                @if ($transaction->notes)
                                    <p class="text-xs text-gray-400 truncate mt-0.5 leading-tight">
                                        {{ $transaction->notes }}
                                    </p>
                                @endif
                            </div>

                            {{-- Category inline select --}}
                            <div class="shrink-0">
                                <select
                                    @change="$wire.updateCategory({{ $transaction->id }}, $event.target.value)"
                                    class="text-xs font-semibold rounded-full py-1 pl-3 pr-7 border-0 outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer transition-colors
                                        {{ $transaction->category_id
                                            ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                            : 'bg-orange-100 text-orange-700 hover:bg-orange-200' }}"
                                    style="appearance: auto;"
                                >
                                    <option value="0" {{ ! $transaction->category_id ? 'selected' : '' }}>
                                        Uncategorized
                                    </option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" {{ $transaction->category_id == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Account badge --}}
                            <span class="shrink-0 text-xs font-semibold rounded-full px-2.5 py-1 {{ $accountBadge }} whitespace-nowrap">
                                {{ $transaction->account->label }}
                            </span>

                            {{-- Amount (gross + net if repayments exist) --}}
                            <div class="shrink-0 w-32 text-right">
                                <span class="text-sm font-bold tabular-nums {{ $isNegative ? 'text-red-500' : 'text-emerald-600' }}">
                                    {{ $isNegative ? '−' : '+' }}&thinsp;€&thinsp;{{ number_format(abs($transaction->amount), 2, ',', '.') }}
                                </span>
                                @if ($transaction->repayments->isNotEmpty())
                                    @php $net = (float) $transaction->amount + $transaction->repayments->sum('amount'); @endphp
                                    <p class="text-xs text-gray-400 tabular-nums mt-0.5">
                                        net {{ $net < 0 ? '−' : '+' }}&thinsp;€&thinsp;{{ number_format(abs($net), 2, ',', '.') }}
                                    </p>
                                @endif
                            </div>

                            {{-- Link repayments button --}}
                            <button
                                wire:click="openLinkModal({{ $transaction->id }})"
                                title="Link repayments"
                                class="opacity-0 group-hover:opacity-100 shrink-0 p-1.5 rounded-lg transition-all
                                    {{ $transaction->repayments->isNotEmpty() ? 'opacity-100 text-emerald-500' : 'text-gray-400 hover:text-emerald-500 hover:bg-emerald-50' }}"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                </svg>
                            </button>

                            {{-- Edit button --}}
                            <button
                                wire:click="startEdit({{ $transaction->id }})"
                                title="Edit"
                                class="opacity-0 group-hover:opacity-100 shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-blue-500 hover:bg-blue-50 transition-all"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                </svg>
                            </button>

                            {{-- Delete with inline confirmation --}}
                            <div x-data="{ confirming: false }" class="shrink-0 w-20 flex items-center justify-end gap-1">
                                {{-- Delete icon (visible on row hover) --}}
                                <button
                                    x-show="!confirming"
                                    x-on:click="confirming = true"
                                    title="Delete"
                                    class="opacity-0 group-hover:opacity-100 p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>

                                {{-- Inline confirm --}}
                                <div x-show="confirming" x-cloak class="flex items-center gap-1">
                                    <button
                                        wire:click="deleteTransaction({{ $transaction->id }})"
                                        class="px-2 py-0.5 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-md transition-colors"
                                    >Yes</button>
                                    <button
                                        x-on:click="confirming = false"
                                        class="px-2 py-0.5 text-xs font-medium text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                                    >No</button>
                                </div>
                            </div>

                        </div>

                        {{-- Nested repayment rows --}}
                        @foreach ($transaction->repayments as $repayment)
                            @php
                                $repaymentBadge = match ($repayment->account->name) {
                                    'rabobank' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-100',
                                    'revolut'  => 'bg-violet-50 text-violet-700 ring-1 ring-violet-100',
                                    'amex'     => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
                                    default    => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <div class="flex items-center gap-4 pl-9 pr-4 py-2 bg-emerald-50/50 border-t border-emerald-100/70">
                                {{-- Indent arrow --}}
                                <svg class="w-3 h-3 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4 4 8 8-8 8" />
                                </svg>

                                {{-- Description --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-600 truncate leading-tight">{{ $repayment->description }}</p>
                                    @if ($repayment->notes)
                                        <p class="text-xs text-gray-400 truncate mt-0.5">{{ $repayment->notes }}</p>
                                    @endif
                                </div>

                                {{-- Account badge --}}
                                <span class="shrink-0 text-xs font-semibold rounded-full px-2.5 py-1 {{ $repaymentBadge }} whitespace-nowrap">
                                    {{ $repayment->account->label }}
                                </span>

                                {{-- Repayment amount --}}
                                <span class="shrink-0 w-32 text-right text-xs font-bold tabular-nums text-emerald-600">
                                    +&thinsp;€&thinsp;{{ number_format(abs($repayment->amount), 2, ',', '.') }}
                                </span>

                                {{-- Unlink button --}}
                                <button
                                    wire:click="unlinkRepayment({{ $repayment->id }})"
                                    title="Unlink repayment"
                                    class="shrink-0 p-1.5 rounded-lg text-gray-300 hover:text-red-400 hover:bg-red-50 transition-all"
                                >
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
        class="fixed bottom-24 right-6 z-40 flex items-center gap-3 bg-white border border-gray-200 rounded-2xl shadow-xl px-4 py-3"
    >
        <span class="text-sm font-medium text-gray-700" x-text="`${selectedIds.length} selected`"></span>
        <button
            @click="$wire.deleteSelected(selectedIds).then(() => { selectedIds = [] })"
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
            Delete selected
        </button>
        <button
            @click="selectedIds = []"
            class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors"
        >
            Clear
        </button>
    </div>

    {{-- ── Floating Action Buttons ──────────────────────────────────────── --}}
    <div class="fixed bottom-6 right-6 z-40 flex items-center gap-2">
        <button
            wire:click="openImportModal"
            class="flex items-center gap-2 bg-white hover:bg-gray-50 active:bg-gray-100 text-gray-700 border border-gray-200 px-4 py-3 rounded-full shadow-md hover:shadow-lg transition-all text-sm font-semibold"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
            </svg>
            Import
        </button>
        <button
            wire:click="openModal"
            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white px-5 py-3 rounded-full shadow-lg hover:shadow-xl transition-all text-sm font-semibold"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add transaction
        </button>
    </div>

    {{-- ── Import Modal ─────────────────────────────────────────────────── --}}
    @if ($showImportModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.closeImportModal()"
        >
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeImportModal"></div>

            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-bold text-gray-900">Import transactions</h2>
                    <button wire:click="closeImportModal" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($importResult !== null)
                    {{-- Result summary --}}
                    <div class="px-6 py-6 space-y-4">
                        <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <div>
                                <p class="text-sm font-bold text-emerald-800">{{ $importResult['imported'] }} transaction{{ $importResult['imported'] !== 1 ? 's' : '' }} imported</p>
                                @if ($importResult['skipped'] > 0)
                                    <p class="text-xs text-emerald-600 mt-0.5">{{ $importResult['skipped'] }} duplicate{{ $importResult['skipped'] !== 1 ? 's' : '' }} skipped</p>
                                @endif
                            </div>
                        </div>

                        @if (count($importResult['errors']) > 0)
                            <div class="p-4 rounded-xl bg-red-50 border border-red-200">
                                <p class="text-xs font-bold text-red-700 mb-2">{{ count($importResult['errors']) }} error{{ count($importResult['errors']) !== 1 ? 's' : '' }}</p>
                                <ul class="space-y-1">
                                    @foreach (array_slice($importResult['errors'], 0, 5) as $error)
                                        <li class="text-xs text-red-600">{{ $error }}</li>
                                    @endforeach
                                    @if (count($importResult['errors']) > 5)
                                        <li class="text-xs text-red-500 italic">… and {{ count($importResult['errors']) - 5 }} more</li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                        <button
                            wire:click="closeImportModal"
                            class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors"
                        >Done</button>
                    </div>

                @else
                    {{-- Import form --}}
                    <div class="px-6 py-5 space-y-4">

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Bank</label>
                            <select
                                wire:model.live="importBank"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                            >
                                <option value="">Select bank…</option>
                                <option value="rabobank">Rabobank (CSV, semicolon-delimited)</option>
                                <option value="revolut">Revolut (CSV)</option>
                                <option value="amex">American Express (CSV)</option>
                            </select>
                            @error('importBank')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Account</label>
                            <select
                                wire:model="importAccountId"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                            >
                                <option value="">Select account…</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->label }}</option>
                                @endforeach
                            </select>
                            @error('importAccountId')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">File</label>
                            <div class="relative">
                                <input
                                    type="file"
                                    wire:model="importFile"
                                    accept=".csv,.txt,.xlsx"
                                    class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Accepts .csv and .xlsx files</p>
                            @error('importFile')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                        <button wire:click="closeImportModal" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                            Cancel
                        </button>
                        <button
                            wire:click="runImport"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-70 cursor-not-allowed"
                            class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors"
                        >
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
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.closeModal()"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                wire:click="closeModal"
            ></div>

            {{-- Modal card --}}
            <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Add transaction</h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ app(\App\Services\PeriodService::class)->formatLabel($period) }}
                        </p>
                    </div>
                    <button
                        wire:click="closeModal"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Form body --}}
                <div class="px-6 py-5 space-y-4">

                    {{-- Row 1: Date + Amount --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date</label>
                            <input
                                type="date"
                                wire:model="newDate"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                            >
                            @error('newDate')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Amount</label>
                            <div class="flex">
                                <span class="flex items-center px-3 text-sm font-medium text-gray-500 bg-gray-50 border border-r-0 border-gray-200 rounded-l-lg select-none">€</span>
                                <input
                                    type="number"
                                    wire:model="newAmount"
                                    step="0.01"
                                    placeholder="-45.00"
                                    class="flex-1 min-w-0 border border-gray-200 rounded-r-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                                >
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Negative = expense, positive = income</p>
                            @error('newAmount')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Description</label>
                        <input
                            type="text"
                            wire:model="newDescription"
                            placeholder="e.g. Albert Heijn, Netflix, Salary..."
                            autofocus
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                        >
                        @error('newDescription')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Row 3: Account + Category --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Account</label>
                            <select
                                wire:model="newAccountId"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                            >
                                <option value="">Select account…</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->label }}</option>
                                @endforeach
                            </select>
                            @error('newAccountId')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                                Category <span class="text-gray-400 normal-case font-normal">(optional)</span>
                            </label>
                            <select
                                wire:model="newCategoryId"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                            >
                                <option value="">Uncategorized</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Notes <span class="text-gray-400 normal-case font-normal">(optional)</span>
                        </label>
                        <textarea
                            wire:model="newNotes"
                            rows="2"
                            placeholder="Any additional details…"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none resize-none"
                        ></textarea>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <button
                        wire:click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-70 cursor-not-allowed"
                        class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors"
                    >
                        <span wire:loading.remove wire:target="save">Save transaction</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ── Edit Transaction Modal ──────────────────────────────────────── --}}
    @if ($showEditModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.cancelEdit()"
        >
            <div
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                wire:click="cancelEdit"
            ></div>

            <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Edit transaction</h2>
                    </div>
                    <button
                        wire:click="cancelEdit"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date</label>
                            <input
                                type="date"
                                wire:model="editDate"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                            >
                            @error('editDate')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Amount</label>
                            <div class="flex">
                                <span class="flex items-center px-3 text-sm font-medium text-gray-500 bg-gray-50 border border-r-0 border-gray-200 rounded-l-lg select-none">€</span>
                                <input
                                    type="number"
                                    wire:model="editAmount"
                                    step="0.01"
                                    placeholder="-45.00"
                                    class="flex-1 min-w-0 border border-gray-200 rounded-r-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                                >
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Negative = expense, positive = income</p>
                            @error('editAmount')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Description</label>
                        <input
                            type="text"
                            wire:model="editDescription"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                        >
                        @error('editDescription')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Account</label>
                            <select
                                wire:model="editAccountId"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                            >
                                <option value="">Select account…</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->label }}</option>
                                @endforeach
                            </select>
                            @error('editAccountId')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                                Category <span class="text-gray-400 normal-case font-normal">(optional)</span>
                            </label>
                            <select
                                wire:model="editCategoryId"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                            >
                                <option value="">Uncategorized</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Notes <span class="text-gray-400 normal-case font-normal">(optional)</span>
                        </label>
                        <textarea
                            wire:model="editNotes"
                            rows="2"
                            placeholder="Any additional details…"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none resize-none"
                        ></textarea>
                    </div>

                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <button
                        wire:click="cancelEdit"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="saveEdit"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-70 cursor-not-allowed"
                        class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-colors"
                    >
                        <span wire:loading.remove wire:target="saveEdit">Save changes</span>
                        <span wire:loading wire:target="saveEdit">Saving…</span>
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ── Link Repayments Modal ───────────────────────────────────────── --}}
    @if ($showLinkModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.closeLinkModal()"
        >
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeLinkModal"></div>

            <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[80vh]">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Link repayments</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Select incoming payments that offset this expense</p>
                    </div>
                    <button wire:click="closeLinkModal" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="overflow-y-auto flex-1">
                    @if ($linkableTransactions->isEmpty())
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <p class="text-sm font-semibold text-gray-600">No linkable transactions found</p>
                            <p class="text-xs text-gray-400 mt-1">Import or add positive/incoming transactions first</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-50">
                            @foreach ($linkableTransactions as $linkable)
                                @php
                                    $linkableBadge = match ($linkable->account->name) {
                                        'rabobank' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-100',
                                        'revolut'  => 'bg-violet-50 text-violet-700 ring-1 ring-violet-100',
                                        'amex'     => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
                                        default    => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <div class="flex items-center gap-4 px-6 py-3 hover:bg-gray-50 transition-colors">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $linkable->description }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $linkable->date->format('j M Y') }}</p>
                                    </div>
                                    <span class="shrink-0 text-xs font-semibold rounded-full px-2.5 py-1 {{ $linkableBadge }} whitespace-nowrap">
                                        {{ $linkable->account->label }}
                                    </span>
                                    <span class="shrink-0 w-24 text-right text-sm font-bold tabular-nums text-emerald-600">
                                        +&thinsp;€&thinsp;{{ number_format(abs($linkable->amount), 2, ',', '.') }}
                                    </span>
                                    <button
                                        wire:click="linkRepayment({{ $linkable->id }})"
                                        class="shrink-0 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg transition-colors"
                                    >
                                        Link
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 shrink-0">
                    <button wire:click="closeLinkModal" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                        Done
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
