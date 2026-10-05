@php
    $allShown = $transactions->flatten(1);
    $shownCount = $allShown->count();
    $shownOut = (float) $allShown->where('amount', '<', 0)->sum('amount');
    $shownIn = (float) $allShown->where('amount', '>', 0)->sum('amount');
    $rowGrid = 'md:grid md:grid-cols-[1rem_minmax(0,1fr)_11rem_8.5rem_5.75rem] md:items-center md:gap-x-4';
    $shortLabel = fn ($account) => $account->name === 'amex' ? 'Amex' : $account->label;
@endphp

<div class="flex min-h-full flex-col" x-data="{ selectedIds: [] }">

    <x-page-header title="Transactions" class="mx-auto w-full max-w-[1180px]">
        <x-slot:meta>
            {{ $period->start_date->format('j F') }} – {{ $period->end_date->format('j F Y') }}
            @if ($uncategorizedCount > 0)
                <span class="px-1 text-line-strong">·</span><span class="text-attention">{{ $uncategorizedCount }} uncategorized</span>
            @endif
        </x-slot:meta>
        <x-slot:actions>
            <button type="button" wire:click="openImportModal" class="btn btn-secondary">
                <svg class="size-4 text-ink-3" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Import
            </button>
            <button type="button" wire:click="openModal" class="btn btn-primary">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add transaction
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- ── Filters ─────────────────────────────────────────────────────── --}}
    <div class="sticky top-0 z-20 border-y border-line bg-canvas">
        <div class="mx-auto flex max-w-[1180px] flex-wrap items-center gap-2 px-5 py-3 sm:px-8">
            <div class="relative w-full sm:w-56">
                <svg class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-ink-3" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search description or notes" aria-label="Search transactions" class="field pl-8">
            </div>

            <div class="-mx-5 flex gap-2 overflow-x-auto px-5 [scrollbar-width:none] sm:contents">
            <div class="flex h-9 shrink-0 items-center gap-0.5 rounded-md border border-line-strong bg-surface p-[3px]" role="radiogroup" aria-label="Account">
                <label class="flex h-full items-center rounded-[4px] px-2.5 text-sm transition-colors has-focus-visible:outline-2 has-focus-visible:outline-accent {{ $filterAccount === '' ? 'bg-sunken font-medium text-ink' : 'text-ink-2 hover:text-ink' }}">
                    <input type="radio" wire:model.live="filterAccount" value="" class="sr-only">
                    All
                </label>
                @foreach ($accounts as $account)
                    <label title="{{ $account->label }}" class="flex h-full items-center gap-1.5 rounded-[4px] px-2 text-sm transition-colors has-focus-visible:outline-2 has-focus-visible:outline-accent {{ $filterAccount === (string) $account->id ? 'bg-sunken font-medium text-ink' : 'text-ink-2 hover:text-ink' }}">
                        <input type="radio" wire:model.live="filterAccount" value="{{ $account->id }}" class="sr-only">
                        <x-provider-logo :provider="$account->name" size="xs" />
                        <span class="hidden 2xl:inline">{{ $shortLabel($account) }}</span>
                    </label>
                @endforeach
            </div>

            <select wire:model.live="filterCategory" aria-label="Category" @disabled($uncategorizedOnly) class="field w-auto shrink-0 disabled:opacity-40 {{ $filterCategory !== '' ? 'border-accent text-ink' : 'text-ink-2' }}">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterType" aria-label="Type" class="field w-auto shrink-0 {{ $filterType !== '' ? 'border-accent text-ink' : 'text-ink-2' }}">
                <option value="">In and out</option>
                <option value="expense">Money out</option>
                <option value="income">Money in</option>
            </select>

            <label class="inline-flex h-9 shrink-0 items-center gap-2 rounded-md border px-3 text-sm transition-colors select-none {{ $uncategorizedOnly ? 'border-attention/40 bg-attention-soft text-attention' : 'border-line-strong bg-surface text-ink-2 hover:text-ink' }}">
                <input type="checkbox" wire:model.live="uncategorizedOnly" class="check">
                Uncategorized
            </label>

            <label class="inline-flex h-9 shrink-0 items-center gap-2 rounded-md border px-3 text-sm transition-colors select-none {{ $filterPendingReturn ? 'border-accent/50 bg-accent-soft text-ink' : 'border-line-strong bg-surface text-ink-2 hover:text-ink' }}">
                <input type="checkbox" wire:model.live="filterPendingReturn" class="check">
                Pending return
            </label>
            </div>

            @if ($hasActiveFilters)
                <button type="button" wire:click="clearFilters" class="btn btn-quiet ml-auto px-2.5">Clear</button>
            @endif
        </div>
    </div>

    {{-- ── Ledger ──────────────────────────────────────────────────────── --}}
    <div class="mx-auto w-full max-w-[1180px] flex-1 px-5 pt-5 pb-32 sm:px-8">

        @if ($transactions->isEmpty())
            <div class="rounded-xl border border-dashed border-line-strong px-6 py-20 text-center">
                @if ($hasActiveFilters)
                    <p class="text-sm font-medium text-ink">No transactions match these filters</p>
                    <p class="mt-1 text-sm text-ink-3">Change the filters, or clear them to see the whole period.</p>
                    <button type="button" wire:click="clearFilters" class="btn btn-secondary mt-5">Clear filters</button>
                @else
                    <p class="text-sm font-medium text-ink">No transactions in this period yet</p>
                    <p class="mt-1 text-sm text-ink-3">Import a bank export or add one by hand.</p>
                    <div class="mt-5 flex justify-center gap-2">
                        <button type="button" wire:click="openImportModal" class="btn btn-secondary">Import</button>
                        <button type="button" wire:click="openModal" class="btn btn-primary">Add transaction</button>
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-xl border border-line bg-surface" x-ref="ledger">

                {{-- Column headings --}}
                <div class="hidden rounded-t-xl border-b border-line px-4 py-2.5 text-xs text-ink-3 {{ $rowGrid }}">
                    <input type="checkbox" class="check" aria-label="Select all shown transactions"
                           :checked="selectedIds.length > 0 && selectedIds.length === $refs.ledger.querySelectorAll('[data-tx-id]').length"
                           @change="selectedIds = $event.target.checked ? [...$refs.ledger.querySelectorAll('[data-tx-id]')].map(el => Number(el.dataset.txId)) : []">
                    <span class="pl-12">Description</span>
                    <span>Category</span>
                    <span class="text-right">Amount</span>
                    <span></span>
                </div>

                @foreach ($transactions as $dateStr => $group)
                    @php
                        $date = \Carbon\Carbon::parse($dateStr);
                        $dayOut = (float) $group->where('amount', '<', 0)->sum('amount');
                        $dayIn = (float) $group->where('amount', '>', 0)->sum('amount');
                    @endphp

                    {{-- Date heading --}}
                    <div class="flex items-baseline justify-between gap-4 border-b border-line bg-sunken/50 px-4 py-2 text-xs {{ $loop->first ? 'max-md:rounded-t-xl' : '' }}">
                        <span class="font-medium text-ink-2">{{ $date->format('l j F') }}</span>
                        <span class="flex gap-3 tabular-nums text-ink-3">
                            @if ($dayIn > 0)<x-money :amount="$dayIn" signed class="text-credit" />@endif
                            @if ($dayOut < 0)<x-money :amount="$dayOut" />@endif
                        </span>
                    </div>

                    @foreach ($group as $transaction)
                        @php
                            $isIncome = $transaction->amount > 0;
                            $categoryColor = $transaction->category?->color;
                            $details = $transaction->notes ?: (str_contains($transaction->description, ' — ') ? Str::after($transaction->description, ' — ') : null);
                        @endphp

                        <div class="group relative flex flex-wrap items-center gap-x-3 border-b border-line px-4 py-3 transition-colors hover:bg-sunken/40 md:py-2.5 {{ $rowGrid }}"
                             data-tx-id="{{ $transaction->id }}"
                             :class="selectedIds.includes({{ $transaction->id }}) && 'bg-accent-soft hover:bg-accent-soft'">

                            {{-- Select --}}
                            <input type="checkbox" class="check shrink-0"
                                   aria-label="Select {{ $transaction->description }}"
                                   :checked="selectedIds.includes({{ $transaction->id }})"
                                   @change="$event.target.checked
                                        ? selectedIds.push({{ $transaction->id }})
                                        : selectedIds = selectedIds.filter(id => id !== {{ $transaction->id }})">

                            {{-- Merchant --}}
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <x-merchant-avatar :transaction="$transaction" />
                                <div class="min-w-0">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <p class="truncate text-sm font-medium text-ink" title="{{ $transaction->description }}">{{ $transaction->merchant_name }}</p>
                                        @if ($transaction->is_pending_return)
                                            <span class="shrink-0 rounded-full bg-attention-soft px-2 text-[0.6875rem] leading-[1.125rem] font-medium text-attention">Pending return</span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 truncate text-xs text-ink-3" title="{{ $details }}">
                                        {{ $shortLabel($transaction->account) }}@if ($details)<span class="px-1">·</span>{{ $details }}@endif
                                    </p>
                                </div>
                            </div>

                            {{-- Category --}}
                            <div class="relative order-last mt-2 w-full basis-full pl-[4.6rem] md:order-none md:mt-0 md:basis-auto md:pl-0">
                                <span class="pointer-events-none absolute top-1/2 left-[5.25rem] z-10 size-2 -translate-y-1/2 rounded-[2px] md:left-2.5 {{ $categoryColor ? '' : 'ring-[1.5px] ring-attention ring-inset' }}"
                                      @if ($categoryColor) style="background-color: {{ $categoryColor }}" @endif></span>
                                <select
                                    @change="$wire.updateCategory({{ $transaction->id }}, $event.target.value)"
                                    aria-label="Category for {{ $transaction->description }}"
                                    class="field h-8 pl-7 text-[0.8125rem] {{ $transaction->category_id ? 'border-transparent bg-transparent text-ink-2 hover:border-line-strong hover:bg-surface' : 'border-attention/30 bg-attention-soft text-attention hover:border-attention/60' }}"
                                >
                                    <option value="0" {{ ! $transaction->category_id ? 'selected' : '' }}>Uncategorized</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" {{ $transaction->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Amount --}}
                            <div class="shrink-0 text-right">
                                <x-money :amount="$transaction->amount" signed
                                         class="text-sm font-medium tabular-nums {{ $transaction->is_pending_return ? 'text-ink-3 line-through decoration-ink-3/60' : ($isIncome ? 'text-credit' : 'text-ink') }}" />
                                @if ($transaction->repayments->isNotEmpty())
                                    @php $net = (float) $transaction->amount + $transaction->repayments->sum('amount'); @endphp
                                    <p class="mt-0.5 text-xs text-ink-3 tabular-nums">net <x-money :amount="$net" /></p>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="flex shrink-0 items-center justify-end gap-0.5 max-md:-mr-1.5" x-data="{ menu: false, confirming: false }" @click.outside="menu = false; confirming = false" @keydown.escape="menu = false; confirming = false">
                                <button type="button" wire:click="openLinkModal({{ $transaction->id }})"
                                        title="{{ $transaction->repayments->isNotEmpty() ? 'Linked repayments' : 'Link repayments' }}"
                                        class="icon-btn hidden md:inline-flex {{ $transaction->repayments->isNotEmpty() ? 'text-credit' : 'md:opacity-0 md:group-hover:opacity-100 md:focus-visible:opacity-100' }}">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                                </button>
                                <button type="button" wire:click="startEdit({{ $transaction->id }})" title="Edit"
                                        class="icon-btn hidden md:inline-flex md:opacity-0 md:group-hover:opacity-100 md:focus-visible:opacity-100">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                </button>
                                <div class="relative">
                                    <button type="button" @click="menu = !menu; confirming = false" title="More actions" :aria-expanded="menu"
                                            class="icon-btn" :class="menu && 'bg-sunken text-ink'">
                                        <svg class="size-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm0 5.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm0 5.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z" /></svg>
                                    </button>
                                    <div x-show="menu" x-cloak x-transition.opacity.duration.75ms
                                         class="absolute top-full right-0 z-30 mt-1 w-56 rounded-lg border border-line bg-surface p-1 text-sm shadow-[0_12px_32px_-8px_rgb(0_0_0/0.3)]">
                                        <div x-show="!confirming">
                                            <button type="button" wire:click="startEdit({{ $transaction->id }})" @click="menu = false" class="flex w-full items-center rounded-md px-2.5 py-1.5 text-left text-ink hover:bg-sunken">Edit</button>
                                            <button type="button" wire:click="openLinkModal({{ $transaction->id }})" @click="menu = false" class="flex w-full items-center rounded-md px-2.5 py-1.5 text-left text-ink hover:bg-sunken">Link repayments</button>
                                            <button type="button" wire:click="togglePendingReturn({{ $transaction->id }})" @click="menu = false" class="flex w-full items-center rounded-md px-2.5 py-1.5 text-left text-ink hover:bg-sunken">
                                                {{ $transaction->is_pending_return ? 'Clear pending return' : 'Mark as pending return' }}
                                            </button>
                                            <div class="my-1 h-px bg-line"></div>
                                            <button type="button" @click="confirming = true" class="flex w-full items-center rounded-md px-2.5 py-1.5 text-left text-over hover:bg-over-soft">Delete</button>
                                        </div>
                                        <div x-show="confirming" class="p-1.5">
                                            <p class="text-[0.8125rem] text-ink-2">Delete this transaction?</p>
                                            <div class="mt-2.5 flex gap-1.5">
                                                <button type="button" wire:click="deleteTransaction({{ $transaction->id }})" class="btn btn-danger btn-sm flex-1">Delete</button>
                                                <button type="button" @click="confirming = false" class="btn btn-secondary btn-sm flex-1">Keep</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Linked repayments --}}
                        @foreach ($transaction->repayments as $repayment)
                            <div class="flex items-center gap-3 border-b border-line bg-sunken/30 px-4 py-2 {{ $rowGrid }}">
                                <span class="hidden md:block"></span>
                                <div class="flex min-w-0 flex-1 items-center gap-2.5 pl-7 md:pl-1.5">
                                    <svg class="size-3.5 shrink-0 text-ink-3" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 4.5v7.5a3 3 0 0 0 3 3h10.5m0 0-3.75-3.75M18.75 15l-3.75 3.75" /></svg>
                                    <x-merchant-avatar :transaction="$repayment" size="sm" />
                                    <div class="min-w-0">
                                        <p class="truncate text-[0.8125rem] text-ink-2" title="{{ $repayment->description }}">{{ $repayment->merchant_name }}</p>
                                        <p class="truncate text-xs text-ink-3">{{ $shortLabel($repayment->account) }} · {{ $repayment->date->format('j M') }}@if ($repayment->notes) · {{ $repayment->notes }}@endif</p>
                                    </div>
                                </div>
                                <span class="hidden text-xs text-ink-3 md:block">Paid back</span>
                                <x-money :amount="$repayment->amount" signed class="shrink-0 text-right text-[0.8125rem] tabular-nums text-credit" />
                                <div class="flex justify-end">
                                    <button type="button" wire:click="unlinkRepayment({{ $repayment->id }})" title="Unlink repayment" class="icon-btn hover:text-over">
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                @endforeach

                {{-- Totals --}}
                <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 rounded-b-xl px-4 py-3 text-[0.8125rem] text-ink-3">
                    <span>{{ $shownCount }} {{ Str::plural('transaction', $shownCount) }}{{ $hasActiveFilters ? ' match the filters' : '' }}</span>
                    <span class="flex gap-5 tabular-nums">
                        <span>In <x-money :amount="$shownIn" class="text-ink-2" /></span>
                        <span>Out <x-money :amount="abs($shownOut)" class="text-ink-2" /></span>
                    </span>
                </div>
            </div>
        @endif
    </div>

    {{-- ── Bulk actions ────────────────────────────────────────────────── --}}
    <div x-show="selectedIds.length > 0" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="pointer-events-none fixed inset-x-0 bottom-6 z-40 flex justify-center pl-14 lg:pl-60">
        <div class="pointer-events-auto flex items-center gap-2 rounded-lg border border-rail-line bg-rail py-1.5 pr-1.5 pl-4 text-sm text-rail-ink shadow-xl" x-data="{ confirming: false }">
            <span class="mr-2 tabular-nums" x-text="`${selectedIds.length} selected`"></span>
            <template x-if="!confirming">
                <div class="flex gap-1">
                    <button type="button" @click="confirming = true" class="btn btn-sm text-rail-ink hover:bg-white/10">Delete</button>
                    <button type="button" @click="selectedIds = []" class="btn btn-sm text-rail-ink-2 hover:bg-white/10 hover:text-rail-ink">Clear selection</button>
                </div>
            </template>
            <template x-if="confirming">
                <div class="flex items-center gap-1">
                    <button type="button" @click="$wire.deleteSelected(selectedIds).then(() => { selectedIds = []; confirming = false })" class="btn btn-danger btn-sm" x-text="`Delete ${selectedIds.length}`"></button>
                    <button type="button" @click="confirming = false" class="btn btn-sm text-rail-ink-2 hover:bg-white/10 hover:text-rail-ink">Keep</button>
                </div>
            </template>
        </div>
    </div>

    {{-- ── Import ──────────────────────────────────────────────────────── --}}
    @if ($showImportModal)
        <x-modal title="Import transactions" subtitle="Upload a CSV export from your bank. Duplicates are skipped." close="closeImportModal" width="max-w-md">
            @if ($importResult !== null)
                <div class="space-y-3 px-5 py-5">
                    <div class="rounded-md border border-line bg-sunken/60 px-4 py-3">
                        <p class="text-sm font-medium text-ink">{{ $importResult['imported'] }} {{ Str::plural('transaction', $importResult['imported']) }} imported</p>
                        @if ($importResult['skipped'] > 0)
                            <p class="mt-0.5 text-[0.8125rem] text-ink-3">{{ $importResult['skipped'] }} {{ Str::plural('duplicate', $importResult['skipped']) }} skipped</p>
                        @endif
                    </div>
                    @if (count($importResult['errors']) > 0)
                        <div class="rounded-md border border-over/30 bg-over-soft px-4 py-3">
                            <p class="text-sm font-medium text-over">{{ count($importResult['errors']) }} {{ Str::plural('row', count($importResult['errors'])) }} could not be read</p>
                            <ul class="mt-1.5 space-y-1">
                                @foreach (array_slice($importResult['errors'], 0, 5) as $error)
                                    <li class="text-[0.8125rem] text-ink-2">{{ $error }}</li>
                                @endforeach
                                @if (count($importResult['errors']) > 5)
                                    <li class="text-[0.8125rem] text-ink-3">and {{ count($importResult['errors']) - 5 }} more</li>
                                @endif
                            </ul>
                        </div>
                    @endif
                </div>
                <x-slot:footer>
                    <button type="button" wire:click="closeImportModal" class="btn btn-primary">Done</button>
                </x-slot:footer>
            @else
                <div class="space-y-4 px-5 py-5">
                    <div>
                        <x-field-label>Export from</x-field-label>
                        <div class="grid grid-cols-3 gap-2" role="radiogroup" aria-label="Bank format">
                            @foreach (['rabobank' => ['Rabobank', 'CSV, semicolons'], 'revolut' => ['Revolut', 'CSV'], 'amex' => ['Amex', 'CSV']] as $bank => [$bankLabel, $bankFormat])
                                <label class="flex flex-col items-center gap-2 rounded-lg border border-line-strong bg-surface px-2 pt-3.5 pb-3 text-center transition-colors hover:border-ink-3 has-checked:border-accent has-checked:bg-accent-soft has-focus-visible:outline-2 has-focus-visible:outline-accent">
                                    <input type="radio" wire:model.live="importBank" value="{{ $bank }}" class="sr-only">
                                    <x-provider-logo :provider="$bank" size="lg" />
                                    <span>
                                        <span class="block text-[0.8125rem] font-medium text-ink">{{ $bankLabel }}</span>
                                        <span class="block text-xs text-ink-3">{{ $bankFormat }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <x-field-error name="importBank" />
                    </div>
                    <div>
                        <x-field-label>Into account</x-field-label>
                        <div class="grid grid-cols-3 gap-2" role="radiogroup" aria-label="Into account">
                            @foreach ($accounts as $account)
                                <label class="flex min-w-0 items-center gap-2.5 rounded-lg border border-line-strong bg-surface px-2.5 py-2 text-[0.8125rem] text-ink-2 transition-colors hover:border-ink-3 has-checked:border-accent has-checked:bg-accent-soft has-checked:text-ink has-focus-visible:outline-2 has-focus-visible:outline-accent">
                                    <input type="radio" wire:model="importAccountId" value="{{ $account->id }}" class="sr-only">
                                    <x-provider-logo :provider="$account->name" size="sm" />
                                    <span class="truncate">{{ $shortLabel($account) }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-field-error name="importAccountId" />
                    </div>
                    <div>
                        <x-field-label for="import-file">File</x-field-label>
                        <input id="import-file" type="file" wire:model="importFile" accept=".csv,.txt,.xlsx" class="field">
                        <p class="mt-1.5 text-[0.8125rem] text-ink-3">.csv or .xlsx</p>
                        <x-field-error name="importFile" />
                    </div>
                </div>
                <x-slot:footer>
                    <button type="button" wire:click="closeImportModal" class="btn btn-quiet">Cancel</button>
                    <button type="button" wire:click="runImport" wire:loading.attr="disabled" class="btn btn-primary">
                        <span wire:loading.remove wire:target="runImport">Import</span>
                        <span wire:loading wire:target="runImport">Importing…</span>
                    </button>
                </x-slot:footer>
            @endif
        </x-modal>
    @endif

    {{-- ── Add transaction ─────────────────────────────────────────────── --}}
    @if ($showModal)
        <x-modal title="Add transaction" :subtitle="app(\App\Services\PeriodService::class)->formatLabel($period)" close="closeModal">
            <div class="space-y-4 px-5 py-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-field-label for="new-date">Date</x-field-label>
                        <input id="new-date" type="date" wire:model="newDate" class="field">
                        <x-field-error name="newDate" />
                    </div>
                    <div>
                        <x-field-label for="new-amount">Amount</x-field-label>
                        <div class="relative">
                            <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-ink-3">€</span>
                            <input id="new-amount" type="number" wire:model="newAmount" step="0.01" placeholder="-45.00" class="field pl-7 tabular-nums">
                        </div>
                        <p class="mt-1.5 text-[0.8125rem] text-ink-3">Use a minus sign for money out</p>
                        <x-field-error name="newAmount" />
                    </div>
                </div>
                <div>
                    <x-field-label for="new-description">Description</x-field-label>
                    <input id="new-description" type="text" wire:model="newDescription" placeholder="Albert Heijn, Netflix, salary…" autofocus class="field">
                    <x-field-error name="newDescription" />
                </div>
<div>
                    <x-field-label>Account</x-field-label>
                    <div class="grid grid-cols-3 gap-2" role="radiogroup" aria-label="Account">
                        @foreach ($accounts as $account)
                            <label class="flex min-w-0 items-center gap-2.5 rounded-lg border border-line-strong bg-surface px-2.5 py-2 text-[0.8125rem] text-ink-2 transition-colors hover:border-ink-3 has-checked:border-accent has-checked:bg-accent-soft has-checked:text-ink has-focus-visible:outline-2 has-focus-visible:outline-accent">
                                <input type="radio" wire:model="newAccountId" value="{{ $account->id }}" class="sr-only">
                                <x-provider-logo :provider="$account->name" size="sm" />
                                <span class="truncate">{{ $shortLabel($account) }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-field-error name="newAccountId" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-field-label for="new-category" optional>Category</x-field-label>
                        <select id="new-category" wire:model="newCategoryId" class="field">
                            <option value="">Uncategorized</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-field-label for="new-notes" optional>Notes</x-field-label>
                        <input id="new-notes" type="text" wire:model="newNotes" class="field">
                    </div>
                </div>
            </div>
            <x-slot:footer>
                <button type="button" wire:click="closeModal" class="btn btn-quiet">Cancel</button>
                <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">
                    <span wire:loading.remove wire:target="save">Add transaction</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- ── Edit transaction ────────────────────────────────────────────── --}}
    @if ($showEditModal)
        <x-modal title="Edit transaction" close="cancelEdit">
            <div class="space-y-4 px-5 py-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-field-label for="edit-date">Date</x-field-label>
                        <input id="edit-date" type="date" wire:model="editDate" class="field">
                        <x-field-error name="editDate" />
                    </div>
                    <div>
                        <x-field-label for="edit-amount">Amount</x-field-label>
                        <div class="relative">
                            <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-ink-3">€</span>
                            <input id="edit-amount" type="number" wire:model="editAmount" step="0.01" class="field pl-7 tabular-nums">
                        </div>
                        <p class="mt-1.5 text-[0.8125rem] text-ink-3">Use a minus sign for money out</p>
                        <x-field-error name="editAmount" />
                    </div>
                </div>
                <div>
                    <x-field-label for="edit-description">Description</x-field-label>
                    <input id="edit-description" type="text" wire:model="editDescription" class="field">
                    <x-field-error name="editDescription" />
                </div>
<div>
                    <x-field-label>Account</x-field-label>
                    <div class="grid grid-cols-3 gap-2" role="radiogroup" aria-label="Account">
                        @foreach ($accounts as $account)
                            <label class="flex min-w-0 items-center gap-2.5 rounded-lg border border-line-strong bg-surface px-2.5 py-2 text-[0.8125rem] text-ink-2 transition-colors hover:border-ink-3 has-checked:border-accent has-checked:bg-accent-soft has-checked:text-ink has-focus-visible:outline-2 has-focus-visible:outline-accent">
                                <input type="radio" wire:model="editAccountId" value="{{ $account->id }}" class="sr-only">
                                <x-provider-logo :provider="$account->name" size="sm" />
                                <span class="truncate">{{ $shortLabel($account) }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-field-error name="editAccountId" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-field-label for="edit-category" optional>Category</x-field-label>
                        <select id="edit-category" wire:model="editCategoryId" class="field">
                            <option value="">Uncategorized</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-field-label for="edit-notes" optional>Notes</x-field-label>
                        <input id="edit-notes" type="text" wire:model="editNotes" class="field">
                    </div>
                </div>
            </div>
            <x-slot:footer>
                <button type="button" wire:click="cancelEdit" class="btn btn-quiet">Cancel</button>
                <button type="button" wire:click="saveEdit" wire:loading.attr="disabled" class="btn btn-primary">
                    <span wire:loading.remove wire:target="saveEdit">Save changes</span>
                    <span wire:loading wire:target="saveEdit">Saving…</span>
                </button>
            </x-slot:footer>
        </x-modal>
    @endif

    {{-- ── Link repayments ─────────────────────────────────────────────── --}}
    @if ($showLinkModal)
        <x-modal title="Link repayments" subtitle="Choose incoming payments that pay back part of this expense." close="closeLinkModal" width="max-w-xl">
            @if ($linkableTransactions->isEmpty())
                <div class="px-5 py-12 text-center">
                    <p class="text-sm font-medium text-ink">Nothing to link yet</p>
                    <p class="mt-1 text-sm text-ink-3">Import or add the incoming payment first.</p>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($linkableTransactions as $linkable)
                        <li class="flex items-center gap-4 px-5 py-2.5 hover:bg-sunken/40">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm text-ink">{{ $linkable->description }}</p>
                                <p class="mt-0.5 flex items-center gap-2 text-xs text-ink-3">
                                    {{ $linkable->date->format('j M Y') }}
                                    <x-account-mark :account="$linkable->account" short />
                                </p>
                            </div>
                            <x-money :amount="$linkable->amount" signed class="shrink-0 text-sm tabular-nums text-credit" />
                            <button type="button" wire:click="linkRepayment({{ $linkable->id }})" class="btn btn-secondary btn-sm shrink-0">Link</button>
                        </li>
                    @endforeach
                </ul>
            @endif
            <x-slot:footer>
                <button type="button" wire:click="closeLinkModal" class="btn btn-primary">Done</button>
            </x-slot:footer>
        </x-modal>
    @endif

</div>
