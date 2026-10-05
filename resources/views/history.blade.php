@extends('layouts.app')

@section('title', 'History – Veltiq Budget')

@section('content')
    @php
        $largestPeriodSpend = max(1, (float) $periods->max('totalSpent'));
        $columns = 'grid-cols-[minmax(0,1fr)_auto_1rem] md:grid-cols-[minmax(10rem,1.2fr)_5.5rem_repeat(3,minmax(0,7rem))_minmax(8rem,1fr)_6.5rem_1rem]';
        $accountColumns = ['rabobank' => 'Rabobank', 'revolut' => 'Revolut', 'amex' => 'Amex'];
    @endphp

    <div class="mx-auto max-w-[1180px] pb-16">
        <x-page-header title="History">
            <x-slot:meta>
                {{ $periods->count() }} closed {{ Str::plural('period', $periods->count()) }} · spending per period, newest first
            </x-slot:meta>
        </x-page-header>

        <div class="px-5 sm:px-8">
            @if ($periods->isEmpty())
                <div class="rounded-xl border border-dashed border-line-strong px-6 py-20 text-center">
                    <p class="text-sm font-medium text-ink">No closed periods yet</p>
                    <p class="mt-1 text-sm text-ink-3">A period closes on the 14th. It appears here after that.</p>
                </div>
            @else
                <div class="rounded-xl border border-line bg-surface">

                    {{-- Column headings --}}
                    <div class="hidden gap-x-4 border-b border-line px-5 py-2.5 text-xs text-ink-3 md:grid {{ $columns }}">
                        <span>Period</span>
                        <span class="text-right">Transactions</span>
                        @foreach ($accountColumns as $name => $label)
                            <span class="flex items-center justify-end gap-1.5">
                                <x-provider-logo :provider="$name" size="xs" />
                                {{ $label }}
                            </span>
                        @endforeach
                        <span class="text-right">Total spent</span>
                        <span class="text-right">AmEx paid</span>
                        <span></span>
                    </div>

                    @foreach ($periods as $item)
                        @php
                            $period = $item['period'];
                            $largestCategorySpend = max(1, (float) $item['categorySummaries']->max('spent'), (float) $item['uncategorizedSpent']);
                        @endphp

                        <details class="group border-b border-line last:border-b-0">
                            <summary class="grid list-none items-center gap-x-4 px-5 py-3.5 transition-colors group-open:bg-sunken/40 group-last:rounded-b-xl hover:bg-sunken/40 [&::-webkit-details-marker]:hidden {{ $columns }} {{ $loop->first ? 'max-md:rounded-t-xl' : '' }}">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm text-ink">{{ $period->start_date->format('j M') }} – {{ $period->end_date->format('j M Y') }}</span>
                                    <span class="mt-0.5 block text-xs text-ink-3 md:hidden">{{ $item['transactionCount'] }} transactions</span>
                                </span>

                                <span class="hidden text-right text-sm text-ink-3 tabular-nums md:block">{{ $item['transactionCount'] }}</span>

                                @foreach ($accountColumns as $name => $label)
                                    @php $accountSpend = $item['accountSummaries']->firstWhere('name', $name)['spent'] ?? 0; @endphp
                                    <span class="hidden text-right text-sm tabular-nums md:block {{ $accountSpend > 0 ? 'text-ink-2' : 'text-ink-3' }}">
                                        @if ($accountSpend > 0)<x-money :amount="$accountSpend" />@else — @endif
                                    </span>
                                @endforeach

                                <span class="flex flex-col items-end gap-1.5">
                                    <x-money :amount="$item['totalSpent']" class="text-sm font-medium tabular-nums text-ink" />
                                    <span class="block h-1 w-24 rounded-full bg-sunken" aria-hidden="true">
                                        <span class="block h-full rounded-full bg-bar" style="width: {{ round($item['totalSpent'] / $largestPeriodSpend * 100, 1) }}%"></span>
                                    </span>
                                </span>

                                <span class="hidden text-right text-[0.8125rem] md:block">
                                    @if ($period->amex_paid_at)
                                        <span class="text-credit">{{ $period->amex_paid_at->format('j M') }}</span>
                                    @else
                                        <span class="text-ink-3">Not recorded</span>
                                    @endif
                                </span>

                                <svg class="size-4 text-ink-3 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </summary>

                            <div class="grid gap-8 border-t border-line px-5 py-5 md:grid-cols-[minmax(0,1fr)_16rem]">
                                <div>
                                    <h3 class="mb-3 text-xs text-ink-3">Spent per category</h3>
                                    @if ($item['categorySummaries']->isEmpty() && $item['uncategorizedSpent'] == 0)
                                        <p class="text-sm text-ink-3">No spending in this period.</p>
                                    @else
                                        <ul class="space-y-2">
                                            @foreach ($item['categorySummaries'] as $category)
                                                <li class="grid grid-cols-[minmax(0,10rem)_minmax(0,1fr)_6.5rem] items-center gap-3 text-sm">
                                                    <span class="flex min-w-0 items-center gap-2">
                                                        <span class="size-2 shrink-0 rounded-[2px]" style="background-color: {{ $category['color'] }}"></span>
                                                        <span class="truncate text-ink-2">{{ $category['name'] }}</span>
                                                    </span>
                                                    <span class="h-1 rounded-full bg-sunken" aria-hidden="true">
                                                        <span class="block h-full rounded-full bg-bar" style="width: {{ round($category['spent'] / $largestCategorySpend * 100, 1) }}%"></span>
                                                    </span>
                                                    <x-money :amount="$category['spent']" class="text-right tabular-nums text-ink" />
                                                </li>
                                            @endforeach
                                            @if ($item['uncategorizedSpent'] > 0)
                                                <li class="grid grid-cols-[minmax(0,10rem)_minmax(0,1fr)_6.5rem] items-center gap-3 text-sm">
                                                    <span class="flex min-w-0 items-center gap-2">
                                                        <span class="size-2 shrink-0 rounded-[2px] ring-[1.5px] ring-attention ring-inset"></span>
                                                        <span class="truncate text-attention">Uncategorized</span>
                                                    </span>
                                                    <span class="h-1 rounded-full bg-sunken" aria-hidden="true">
                                                        <span class="block h-full rounded-full bg-attention/60" style="width: {{ round($item['uncategorizedSpent'] / $largestCategorySpend * 100, 1) }}%"></span>
                                                    </span>
                                                    <x-money :amount="$item['uncategorizedSpent']" class="text-right tabular-nums text-attention" />
                                                </li>
                                            @endif
                                        </ul>
                                    @endif
                                </div>

                                <div class="space-y-5">
                                    <div class="md:hidden">
                                        <h3 class="mb-3 text-xs text-ink-3">Spent per account</h3>
                                        <ul class="space-y-2 text-sm">
                                            @foreach ($item['accountSummaries'] as $account)
                                                <li class="flex justify-between gap-3">
                                                    <span class="flex items-center gap-2 text-ink-2"><x-provider-logo :provider="$account['name']" size="xs" />{{ $account['label'] }}</span>
                                                    <x-money :amount="$account['spent']" class="tabular-nums text-ink" />
                                                </li>
                                            @endforeach
                                            <li class="flex justify-between gap-3">
                                                <span class="text-ink-2">AmEx paid</span>
                                                <span class="{{ $period->amex_paid_at ? 'text-credit' : 'text-ink-3' }}">{{ $period->amex_paid_at?->format('j M Y') ?? 'Not recorded' }}</span>
                                            </li>
                                        </ul>
                                    </div>

                                    <a href="{{ route('history.period.transactions', $period) }}" class="btn btn-secondary w-full">
                                        Open transactions
                                        <svg class="size-4 text-ink-3" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                    </a>
                                </div>
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
