@extends('layouts.app')

@section('title', 'History – Veltiq Budget')

@section('content')
    <div class="px-6 py-8">

        @if ($periods->isEmpty())
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4" style="background-color: var(--c-elevated); border: 1px solid var(--c-border);">
                    <svg class="w-6 h-6" style="color: var(--c-text-3);" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold" style="color: var(--c-text-2);">No past periods yet</p>
                <p class="text-xs mt-1" style="color: var(--c-text-3);">Previous periods will appear here once the current one rolls over.</p>
            </div>
        @else
            <div class="space-y-4 max-w-4xl">
                @foreach ($periods as $item)
                    @php
                        $period = $item['period'];
                        $label = $period->start_date->format('j M') . ' – ' . $period->end_date->format('j M Y');
                    @endphp

                    <div class="rounded-2xl overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border);">

                        {{-- Card header --}}
                        <div class="flex items-center justify-between px-5 py-4" style="border-bottom: 1px solid var(--c-border);">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold" style="color: var(--c-text-1);">{{ $label }}</span>
                                <span class="text-xs tabular-nums" style="color: var(--c-text-3);">{{ $item['transactionCount'] }} transactions</span>
                            </div>

                            <div class="flex items-center gap-3">
                                @if ($period->amex_paid_at)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background-color: var(--c-success-bg); color: var(--c-income); border: 1px solid var(--c-success-border);">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        AmEx paid {{ $period->amex_paid_at->format('j M') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium" style="background-color: var(--c-border); color: var(--c-text-3);">
                                        AmEx not recorded
                                    </span>
                                @endif

                                <a
                                    href="{{ route('history.period.transactions', $period) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors"
                                    style="background-color: var(--c-brand-dim); color: var(--c-brand);"
                                    onmouseover="this.style.backgroundColor='rgba(124,111,247,0.2)'"
                                    onmouseout="this.style.backgroundColor='rgba(124,111,247,0.12)'"
                                >
                                    View transactions
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        {{-- Card body: two columns --}}
                        <div class="grid grid-cols-2">

                            {{-- Account breakdown --}}
                            <div class="px-5 py-4" style="border-right: 1px solid var(--c-border-subtle);">
                                <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--c-text-3);">By Account</p>

                                @if ($item['accountSummaries']->isEmpty())
                                    <p class="text-xs" style="color: var(--c-text-3);">No expenses</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($item['accountSummaries'] as $account)
                                            @php
                                                $badgeStyle = match ($account['name']) {
                                                    'rabobank' => 'background-color: var(--c-rabo-bg); color: var(--c-rabo-text);',
                                                    'revolut'  => 'background-color: var(--c-revolut-bg); color: var(--c-revolut-text);',
                                                    'amex'     => 'background-color: var(--c-amex-bg); color: var(--c-amex-text);',
                                                    default    => 'background-color: var(--c-border); color: var(--c-text-2);',
                                                };
                                            @endphp
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="{{ $badgeStyle }}">
                                                    {{ $account['label'] }}
                                                </span>
                                                <span class="text-sm font-semibold tabular-nums" style="color: var(--c-text-1);">
                                                    €&thinsp;{{ number_format($account['spent'], 2, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Category breakdown --}}
                            <div class="px-5 py-4">
                                <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--c-text-3);">By Category</p>

                                @if ($item['categorySummaries']->isEmpty() && $item['uncategorizedSpent'] == 0)
                                    <p class="text-xs" style="color: var(--c-text-3);">No expenses</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($item['categorySummaries'] as $cat)
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $cat['color'] }}"></span>
                                                    <span class="text-xs truncate" style="color: var(--c-text-2);">{{ $cat['name'] }}</span>
                                                </div>
                                                <span class="text-sm font-semibold tabular-nums shrink-0" style="color: var(--c-text-1);">
                                                    €&thinsp;{{ number_format($cat['spent'], 2, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach

                                        @if ($item['uncategorizedSpent'] > 0)
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: var(--c-warn);"></span>
                                                    <span class="text-xs truncate" style="color: var(--c-pending-text);">Uncategorized</span>
                                                </div>
                                                <span class="text-sm font-semibold tabular-nums shrink-0" style="color: var(--c-pending-text);">
                                                    €&thinsp;{{ number_format($item['uncategorizedSpent'], 2, ',', '.') }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                        </div>

                        {{-- Card footer: total --}}
                        <div class="flex items-center justify-between px-5 py-3" style="background-color: var(--c-footer); border-top: 1px solid var(--c-border);">
                            <span class="text-xs font-semibold" style="color: var(--c-text-3);">Total spent</span>
                            <span class="text-sm font-bold tabular-nums" style="color: var(--c-expense);">
                                €&thinsp;{{ number_format($item['totalSpent'], 2, ',', '.') }}
                            </span>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif

    </div>
@endsection
