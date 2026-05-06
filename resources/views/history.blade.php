@extends('layouts.app')

@section('title', 'History – Veltiq Budget')

@section('content')
    <div class="px-6 py-6">

        @if ($periods->isEmpty())
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-4 shadow-inner">
                    <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700">No past periods yet</p>
                <p class="text-xs text-gray-400 mt-1">Previous periods will appear here once the current one rolls over.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($periods as $item)
                    @php
                        $period = $item['period'];
                        $label = $period->start_date->format('j M') . ' – ' . $period->end_date->format('j M Y');
                    @endphp

                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">

                        {{-- Card header --}}
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-gray-900">{{ $label }}</span>
                                <span class="text-xs text-gray-400 tabular-nums">{{ $item['transactionCount'] }} transactions</span>
                            </div>

                            <div class="flex items-center gap-3">
                                {{-- AmEx paid badge --}}
                                @if ($period->amex_paid_at)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 border border-emerald-200 rounded-full text-xs font-semibold text-emerald-700">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        AmEx paid {{ $period->amex_paid_at->format('j M') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 rounded-full text-xs font-medium text-gray-500">
                                        AmEx not recorded
                                    </span>
                                @endif

                                {{-- View transactions link --}}
                                <a
                                    href="{{ route('history.period.transactions', $period) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors"
                                >
                                    View transactions
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        {{-- Card body: two columns --}}
                        <div class="grid grid-cols-2 divide-x divide-gray-50">

                            {{-- Account breakdown --}}
                            <div class="px-5 py-4">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">By Account</p>

                                @if ($item['accountSummaries']->isEmpty())
                                    <p class="text-xs text-gray-400">No expenses</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($item['accountSummaries'] as $account)
                                            @php
                                                $badgeClass = match ($account['name']) {
                                                    'rabobank' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-100',
                                                    'revolut'  => 'bg-violet-50 text-violet-700 ring-1 ring-violet-100',
                                                    'amex'     => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
                                                    default    => 'bg-gray-100 text-gray-600',
                                                };
                                            @endphp
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $badgeClass }}">
                                                    {{ $account['label'] }}
                                                </span>
                                                <span class="text-sm font-semibold tabular-nums text-gray-800">
                                                    €&thinsp;{{ number_format($account['spent'], 2, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Category breakdown --}}
                            <div class="px-5 py-4">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">By Category</p>

                                @if ($item['categorySummaries']->isEmpty() && $item['uncategorizedSpent'] == 0)
                                    <p class="text-xs text-gray-400">No expenses</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($item['categorySummaries'] as $cat)
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $cat['color'] }}"></span>
                                                    <span class="text-xs text-gray-700 truncate">{{ $cat['name'] }}</span>
                                                </div>
                                                <span class="text-sm font-semibold tabular-nums text-gray-800 shrink-0">
                                                    €&thinsp;{{ number_format($cat['spent'], 2, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach

                                        @if ($item['uncategorizedSpent'] > 0)
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <svg class="w-2 h-2 text-orange-400 shrink-0" fill="currentColor" viewBox="0 0 8 8">
                                                        <circle cx="4" cy="4" r="4" />
                                                    </svg>
                                                    <span class="text-xs text-orange-600 truncate">Uncategorized</span>
                                                </div>
                                                <span class="text-sm font-semibold tabular-nums text-orange-600 shrink-0">
                                                    €&thinsp;{{ number_format($item['uncategorizedSpent'], 2, ',', '.') }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                        </div>

                        {{-- Card footer: total --}}
                        <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-t border-gray-100">
                            <span class="text-xs font-semibold text-gray-500">Total spent</span>
                            <span class="text-sm font-bold tabular-nums text-gray-900">
                                €&thinsp;{{ number_format($item['totalSpent'], 2, ',', '.') }}
                            </span>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif

    </div>
@endsection
