<div class="px-6 py-6 space-y-8">

    {{-- ── Uncategorized warning banner ───────────────────────────────────── --}}
    @if ($uncategorizedCount > 0)
        <div class="flex items-center justify-between gap-4 px-5 py-4 bg-orange-50 border border-orange-200 rounded-xl">
            <div class="flex items-center gap-3">
                <svg class="w-4 h-4 text-orange-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <span class="text-sm font-semibold text-orange-800">
                    {{ $uncategorizedCount }} {{ $uncategorizedCount === 1 ? 'transaction needs' : 'transactions need' }} a category
                </span>
            </div>
            <a
                href="{{ route('transactions') }}?uncategorizedOnly=1"
                class="shrink-0 text-sm font-semibold text-orange-700 hover:text-orange-900 underline underline-offset-2"
            >
                Review →
            </a>
        </div>
    @endif

    {{-- ── Period Overview ────────────────────────────────────────────────── --}}
    <section>
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Income</p>
                <p class="text-2xl font-bold tabular-nums text-emerald-600">
                    +&thinsp;€&thinsp;{{ number_format($periodOverview['income'], 2, ',', '.') }}
                </p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Expenses</p>
                <p class="text-2xl font-bold tabular-nums text-red-500">
                    −&thinsp;€&thinsp;{{ number_format($periodOverview['expenses'], 2, ',', '.') }}
                </p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Net</p>
                @php $overviewNet = $periodOverview['net']; @endphp
                <p class="text-2xl font-bold tabular-nums {{ $overviewNet >= 0 ? 'text-gray-900' : 'text-red-500' }}">
                    {{ $overviewNet >= 0 ? '+' : '−' }}&thinsp;€&thinsp;{{ number_format(abs($overviewNet), 2, ',', '.') }}
                </p>
            </div>
        </div>
    </section>

    {{-- ── Section 1: Account Summary Cards ──────────────────────────────── --}}
    <section>
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Account Summary</h2>
        <div class="grid grid-cols-3 gap-4">
            @foreach ($accountSummaries as $summary)
                @php
                    $account = $summary['account'];
                    $badgeClass = match ($account->name) {
                        'rabobank' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-100',
                        'revolut'  => 'bg-violet-50 text-violet-700 ring-1 ring-violet-100',
                        'amex'     => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
                        default    => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $badgeClass }}">
                            {{ $account->label }}
                        </span>
                        <span class="text-xs text-gray-400 tabular-nums">{{ $summary['transactionCount'] }} tx</span>
                    </div>

                    <div class="mb-3">
                        <p class="text-2xl font-bold text-gray-900 tabular-nums">
                            €&thinsp;{{ number_format($summary['totalSpent'], 2, ',', '.') }}
                        </p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <p class="text-xs text-gray-400">spent this period</p>
                            @if ($summary['previousSpent'] !== null && $summary['previousSpent'] > 0)
                                @php $delta = round((($summary['totalSpent'] - $summary['previousSpent']) / $summary['previousSpent']) * 100); @endphp
                                <span class="text-xs font-semibold tabular-nums {{ $delta > 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                    {{ $delta > 0 ? '↑' : '↓' }}&thinsp;{{ abs($delta) }}%
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($summary['uncategorizedCount'] > 0)
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-orange-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            <span class="text-xs font-semibold text-orange-600">
                                {{ $summary['uncategorizedCount'] }} uncategorized
                            </span>
                        </div>
                    @else
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span class="text-xs text-gray-400">All categorized</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── Section 2: Category Progress Bars ──────────────────────────────── --}}
    <section>
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Budget Progress</h2>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            @foreach ($categoryProgress as $index => $row)
                @php
                    $isLast = $index === count($categoryProgress) - 1;
                    $isGoalCategory = in_array($row['category']->type, ['savings', 'investment']);
                    $barColor = match (true) {
                        $row['percentage'] === null                        => 'bg-gray-300',
                        $row['percentage'] > 100 && $isGoalCategory       => 'bg-emerald-500',
                        $row['percentage'] > 100                          => 'bg-red-500',
                        $row['percentage'] > 80 && ! $isGoalCategory      => 'bg-orange-400',
                        default                                           => 'bg-blue-500',
                    };
                    $fillPct = $row['percentage'] !== null ? min(100, $row['percentage']) : 0;
                @endphp
                <div class="px-5 py-4 {{ $isLast ? '' : 'border-b border-gray-50' }}">
                    <div class="flex items-center gap-3 mb-2.5">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $row['category']->color }}"></span>
                        <span class="text-sm font-semibold text-gray-800 flex-1 min-w-0 truncate">{{ $row['category']->name }}</span>

                        <span class="text-sm font-bold tabular-nums text-gray-900 shrink-0">
                            €&thinsp;{{ number_format($row['spent'], 2, ',', '.') }}
                        </span>

                        @if ($row['target'] !== null)
                            <span class="text-xs text-gray-400 tabular-nums shrink-0">
                                / €&thinsp;{{ number_format($row['target'], 2, ',', '.') }}
                            </span>
                            <span class="text-xs font-semibold tabular-nums w-12 text-right shrink-0
                                {{ $row['percentage'] > 100 && $isGoalCategory ? 'text-emerald-600' : ($row['percentage'] > 100 ? 'text-red-500' : 'text-gray-500') }}">
                                {{ $row['percentage'] }}%
                            </span>
                        @else
                            <a href="{{ route('settings') }}" class="text-xs text-blue-500 hover:text-blue-700 hover:underline shrink-0">
                                Set target
                            </a>
                        @endif
                    </div>

                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full {{ $barColor }} rounded-full transition-all duration-300"
                             style="width: {{ $fillPct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── Section 3: AmEx Payoff Split ───────────────────────────────────── --}}
    @if ($amexSplit['hasTransactions'])
        <section>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest">AmEx Payoff</h2>

                @if ($period->amex_paid_at)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 border border-emerald-200 rounded-full text-xs font-semibold text-emerald-700">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Paid on {{ $period->amex_paid_at->format('j M Y') }}
                    </span>
                @else
                    <button
                        wire:click="openAmexModal"
                        class="px-4 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm"
                    >
                        Mark as paid
                    </button>
                @endif
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                @foreach ($amexSplit['lines'] as $line)
                    <div class="flex items-center gap-4 px-5 py-3.5 border-b border-gray-50">
                        <span class="flex-1 text-sm text-gray-700">{{ $line['label'] }}</span>
                        <span class="text-xs text-gray-400 shrink-0">{{ $line['payFrom'] }}</span>
                        <span class="text-sm font-semibold tabular-nums text-gray-900 w-28 text-right shrink-0">
                            €&thinsp;{{ number_format($line['amount'], 2, ',', '.') }}
                        </span>
                    </div>
                @endforeach

                <div class="flex items-center gap-4 px-5 py-4 bg-gray-50">
                    <span class="flex-1 text-sm font-bold text-gray-900">Total</span>
                    <span class="text-xs text-gray-400 shrink-0"></span>
                    <span class="text-sm font-bold tabular-nums text-gray-900 w-28 text-right shrink-0">
                        €&thinsp;{{ number_format($amexSplit['total'], 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </section>
    @endif

    {{-- ── Section 4: Net Worth Snapshot ──────────────────────────────────── --}}
    <section>
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Net Worth</h2>

        @if ($netWorthAccounts->isEmpty())
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-10 text-center">
                <p class="text-sm text-gray-500">No net worth accounts configured.</p>
                <a href="{{ route('settings') }}" class="text-sm text-blue-500 hover:underline mt-1 inline-block">
                    Add accounts in Settings
                </a>
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                @foreach ($netWorthAccounts as $index => $nwAccount)
                    @php
                        $latestSnapshot = $nwAccount->latestSnapshot;
                        $balance = (float) ($latestSnapshot?->balance ?? 0);
                        $isLast = $index === $netWorthAccounts->count() - 1;
                        $typeBadge = match ($nwAccount->type) {
                            'savings'    => 'bg-emerald-50 text-emerald-700',
                            'investment' => 'bg-violet-50 text-violet-700',
                            default      => 'bg-gray-100 text-gray-600',
                        };
                    @endphp

                    <div
                        class="flex items-center gap-4 px-5 py-4 {{ $isLast ? '' : 'border-b border-gray-50' }}"
                        x-data="{ editing: false, newBalance: {{ $balance }} }"
                    >
                        {{-- Account info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900">{{ $nwAccount->name }}</span>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $typeBadge }}">
                                    {{ ucfirst($nwAccount->type) }}
                                </span>
                            </div>
                            @if ($latestSnapshot?->recorded_at)
                                <span class="text-xs text-gray-400 mt-0.5 block">
                                    Updated {{ $latestSnapshot->recorded_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400 mt-0.5 block">No balance recorded yet</span>
                            @endif
                        </div>

                        {{-- Balance + edit controls --}}
                        <div class="flex items-center gap-3 shrink-0">
                            <span x-show="!editing" class="text-base font-bold tabular-nums text-gray-900">
                                €&thinsp;{{ number_format($balance, 2, ',', '.') }}
                            </span>

                            <div x-show="editing" x-cloak class="flex items-center gap-2">
                                <div class="flex">
                                    <span class="flex items-center px-2.5 text-sm text-gray-500 bg-gray-50 border border-r-0 border-gray-200 rounded-l-lg select-none">€</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        x-model="newBalance"
                                        x-ref="balanceInput"
                                        @keydown.enter="$wire.updateNetWorthBalance({{ $nwAccount->id }}, String(newBalance)); editing = false"
                                        @keydown.escape="editing = false"
                                        class="w-32 border border-gray-200 rounded-r-lg px-2.5 py-1.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                                    >
                                </div>
                                <button
                                    @click="$wire.updateNetWorthBalance({{ $nwAccount->id }}, String(newBalance)); editing = false"
                                    class="px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                                >Save</button>
                                <button
                                    @click="editing = false"
                                    class="px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                                >Cancel</button>
                            </div>

                            <button
                                x-show="!editing"
                                @click="editing = true; $nextTick(() => $refs.balanceInput.focus())"
                                title="Update balance"
                                class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach

                {{-- Total row --}}
                <div class="flex items-center justify-between px-5 py-4 bg-gray-50 border-t border-gray-100">
                    <span class="text-sm font-bold text-gray-900">Total Net Worth</span>
                    <span class="text-xl font-bold tabular-nums {{ $netWorthTotal >= 0 ? 'text-gray-900' : 'text-red-500' }}">
                        €&thinsp;{{ number_format($netWorthTotal, 2, ',', '.') }}
                    </span>
                </div>
            </div>
        @endif
    </section>

    {{-- ── AmEx Paid Modal ─────────────────────────────────────────────────── --}}
    @if ($showAmexModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.closeAmexModal()"
        >
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeAmexModal"></div>

            <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-bold text-gray-900">Mark AmEx as paid</h2>
                    <button
                        wire:click="closeAmexModal"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Payment date</label>
                        <input
                            type="date"
                            wire:model="amexPayDate"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                        >
                        @error('amexPayDate')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <p class="text-xs text-gray-500">
                        Total to pay:
                        <span class="font-semibold text-gray-900">€&thinsp;{{ number_format($amexSplit['total'], 2, ',', '.') }}</span>
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <button
                        wire:click="closeAmexModal"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="markAmexPaid"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-70 cursor-not-allowed"
                        class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors"
                    >
                        <span wire:loading.remove wire:target="markAmexPaid">Confirm payment</span>
                        <span wire:loading wire:target="markAmexPaid">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
