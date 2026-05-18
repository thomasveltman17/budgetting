<div class="px-8 py-8 space-y-8 max-w-6xl">

    {{-- ── Uncategorized warning banner ───────────────────────────────────── --}}
    @if ($uncategorizedCount > 0)
        <div class="flex items-center justify-between gap-4 px-5 py-4 rounded-2xl" style="background-color: var(--c-warn-bg); border: 1px solid var(--c-warn-border);">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" style="background-color: var(--c-warn-bg);">
                    <svg class="w-4 h-4" style="color: var(--c-warn);" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <span class="text-sm font-semibold" style="color: var(--c-text-1);">
                    {{ $uncategorizedCount }} {{ $uncategorizedCount === 1 ? 'transaction needs' : 'transactions need' }} a category
                </span>
            </div>
            <a href="{{ route('transactions') }}?uncategorizedOnly=1"
               class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-lg th-link"
               style="background-color: var(--c-warn-bg);"
            >Review →</a>
        </div>
    @endif

    {{-- ── Period Overview (hero stats) ──────────────────────────────────── --}}
    <section>
        <div class="grid grid-cols-3 gap-4">
            {{-- Income --}}
            <div class="rounded-2xl p-6 relative overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border);">
                <div class="absolute inset-x-0 top-0 h-px" style="background: linear-gradient(90deg, transparent, rgba(52,211,153,0.5), transparent);"></div>
                <p class="text-xs font-semibold uppercase tracking-widest mb-3" style="color: var(--c-text-3);">Income</p>
                <p class="text-3xl font-bold tabular-nums" style="color: var(--c-income);">
                    +&thinsp;€&thinsp;{{ number_format($periodOverview['income'], 2, ',', '.') }}
                </p>
                <p class="text-xs mt-2" style="color: var(--c-text-3);">this period</p>
            </div>

            {{-- Expenses --}}
            <div class="rounded-2xl p-6 relative overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border);">
                <div class="absolute inset-x-0 top-0 h-px" style="background: linear-gradient(90deg, transparent, rgba(248,113,113,0.5), transparent);"></div>
                <p class="text-xs font-semibold uppercase tracking-widest mb-3" style="color: var(--c-text-3);">Expenses</p>
                <p class="text-3xl font-bold tabular-nums" style="color: var(--c-expense);">
                    −&thinsp;€&thinsp;{{ number_format($periodOverview['expenses'], 2, ',', '.') }}
                </p>
                <p class="text-xs mt-2" style="color: var(--c-text-3);">this period</p>
            </div>

            {{-- Net --}}
            @php $overviewNet = $periodOverview['net']; @endphp
            <div class="rounded-2xl p-6 relative overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border);">
                <div class="absolute inset-x-0 top-0 h-px" style="background: linear-gradient(90deg, transparent, rgba(124,111,247,0.6), transparent);"></div>
                <p class="text-xs font-semibold uppercase tracking-widest mb-3" style="color: var(--c-text-3);">Net</p>
                <p class="text-3xl font-bold tabular-nums" style="color: var({{ $overviewNet >= 0 ? '--c-text-1' : '--c-expense' }});">
                    {{ $overviewNet >= 0 ? '+' : '−' }}&thinsp;€&thinsp;{{ number_format(abs($overviewNet), 2, ',', '.') }}
                </p>
                <p class="text-xs mt-2" style="color: var(--c-text-3);">income − expenses</p>
            </div>
        </div>
    </section>

    {{-- ── Account Summary Cards ───────────────────────────────────────────── --}}
    <section>
        <h2 class="text-xs font-semibold uppercase tracking-widest mb-4" style="color: var(--c-text-3); letter-spacing: 0.12em;">Accounts</h2>
        <div class="grid grid-cols-3 gap-4">
            @foreach ($accountSummaries as $summary)
                @php
                    $account = $summary['account'];
                    $accentGradient = match ($account->name) {
                        'rabobank' => 'linear-gradient(90deg, transparent, rgba(96,165,250,0.6), transparent)',
                        'revolut'  => 'linear-gradient(90deg, transparent, rgba(167,139,250,0.6), transparent)',
                        'amex'     => 'linear-gradient(90deg, transparent, rgba(52,211,153,0.6), transparent)',
                        default    => 'linear-gradient(90deg, transparent, rgba(124,111,247,0.3), transparent)',
                    };
                    $accentColorVar = match ($account->name) {
                        'rabobank' => 'var(--c-rabo-text)',
                        'revolut'  => 'var(--c-revolut-text)',
                        'amex'     => 'var(--c-amex-text)',
                        default    => 'var(--c-text-2)',
                    };
                    $accentBgVar = match ($account->name) {
                        'rabobank' => 'var(--c-rabo-bg)',
                        'revolut'  => 'var(--c-revolut-bg)',
                        'amex'     => 'var(--c-amex-bg)',
                        default    => 'var(--c-hover)',
                    };
                @endphp
                <div class="rounded-2xl p-5 relative overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border);">
                    <div class="absolute inset-x-0 top-0 h-px" style="background: {{ $accentGradient }};"></div>

                    <div class="flex items-center justify-between mb-5">
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full" style="color: {{ $accentColorVar }}; background-color: {{ $accentBgVar }};">
                            {{ $account->label }}
                        </span>
                        <span class="text-xs tabular-nums" style="color: var(--c-text-3);">{{ $summary['transactionCount'] }} tx</span>
                    </div>

                    <p class="text-2xl font-bold tabular-nums mb-1" style="color: var(--c-text-1);">
                        €&thinsp;{{ number_format($summary['totalSpent'], 2, ',', '.') }}
                    </p>
                    <div class="flex items-center gap-2 mb-4">
                        <p class="text-xs" style="color: var(--c-text-3);">spent this period</p>
                        @if ($summary['previousSpent'] !== null && $summary['previousSpent'] > 0)
                            @php $delta = round((($summary['totalSpent'] - $summary['previousSpent']) / $summary['previousSpent']) * 100); @endphp
                            <span class="text-xs font-semibold tabular-nums px-1.5 py-0.5 rounded-md"
                                  style="{{ $delta > 0 ? 'color: var(--c-expense); background-color: var(--c-expense-bg);' : 'color: var(--c-income); background-color: var(--c-income-bg);' }}">
                                {{ $delta > 0 ? '↑' : '↓' }} {{ abs($delta) }}%
                            </span>
                        @endif
                    </div>

                    @if ($summary['uncategorizedCount'] > 0)
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3 h-3 shrink-0" style="color: var(--c-warn);" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            <span class="text-xs font-semibold" style="color: var(--c-warn);">{{ $summary['uncategorizedCount'] }} uncategorized</span>
                        </div>
                    @else
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3 h-3 shrink-0" style="color: var(--c-income);" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span class="text-xs" style="color: var(--c-text-3);">All categorized</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── Budget Progress ─────────────────────────────────────────────────── --}}
    <section>
        <h2 class="text-xs font-semibold uppercase tracking-widest mb-4" style="color: var(--c-text-3); letter-spacing: 0.12em;">Budget</h2>
        <div class="rounded-2xl overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border);">
            @foreach ($categoryProgress as $index => $row)
                @php
                    $isLast = $index === count($categoryProgress) - 1;
                    $isGoalCategory = in_array($row['category']->type, ['savings', 'investment']);
                    $barGradient = match (true) {
                        $row['percentage'] === null                   => 'background-color: var(--c-elevated);',
                        $row['percentage'] > 100 && $isGoalCategory  => 'background: linear-gradient(90deg, #34D399, #10B981);',
                        $row['percentage'] > 100                     => 'background: linear-gradient(90deg, #F87171, #EF4444);',
                        $row['percentage'] > 80 && ! $isGoalCategory => 'background: linear-gradient(90deg, #FBBF24, #F59E0B);',
                        default                                      => 'background: linear-gradient(90deg, #7C6FF7, #6366F1);',
                    };
                    $pctColorVar = match (true) {
                        $row['percentage'] === null                   => 'var(--c-text-3)',
                        $row['percentage'] > 100 && $isGoalCategory  => 'var(--c-income)',
                        $row['percentage'] > 100                     => 'var(--c-expense)',
                        $row['percentage'] > 80 && ! $isGoalCategory => 'var(--c-warn)',
                        default                                      => 'var(--c-text-2)',
                    };
                    $fillPct = $row['percentage'] !== null ? min(100, $row['percentage']) : 0;
                @endphp
                <div class="px-6 py-4" style="{{ $isLast ? '' : 'border-bottom: 1px solid var(--c-border-subtle);' }}">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $row['category']->color }}"></span>
                        <span class="text-sm font-medium flex-1 min-w-0 truncate" style="color: var(--c-text-1);">{{ $row['category']->name }}</span>

                        <span class="text-sm font-bold tabular-nums shrink-0" style="color: var(--c-text-1);">
                            €&thinsp;{{ number_format($row['spent'], 2, ',', '.') }}
                        </span>

                        @if ($row['target'] !== null)
                            <span class="text-xs tabular-nums shrink-0" style="color: var(--c-text-3);">
                                / €&thinsp;{{ number_format($row['target'], 2, ',', '.') }}
                            </span>
                            <span class="text-xs font-bold tabular-nums w-10 text-right shrink-0" style="color: {{ $pctColorVar }};">
                                {{ $row['percentage'] }}%
                            </span>
                        @else
                            <a href="{{ route('settings') }}" class="th-link text-xs shrink-0">Set target</a>
                        @endif
                    </div>

                    <div class="h-1.5 rounded-full overflow-hidden" style="background-color: var(--c-elevated);">
                        <div class="h-full rounded-full transition-all duration-500" style="{{ $barGradient }} width: {{ $fillPct }}%;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── AmEx Payoff ─────────────────────────────────────────────────────── --}}
    @if ($amexSplit['hasTransactions'])
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-semibold uppercase tracking-widest" style="color: var(--c-text-3); letter-spacing: 0.12em;">AmEx Payoff</h2>

                @if ($period->amex_paid_at)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold" style="color: var(--c-income); background-color: var(--c-income-bg); border: 1px solid var(--c-success-border);">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Paid {{ $period->amex_paid_at->format('j M Y') }}
                    </span>
                @else
                    <button
                        wire:click="openAmexModal"
                        class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-colors"
                        style="color: var(--c-income); background-color: var(--c-income-bg); border: 1px solid var(--c-success-border);"
                        onmouseover="this.style.opacity='0.8'"
                        onmouseout="this.style.opacity='1'"
                    >Mark as paid</button>
                @endif
            </div>

            <div class="rounded-2xl overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border);">
                @foreach ($amexSplit['lines'] as $line)
                    <div class="flex items-center gap-4 px-6 py-4" style="border-bottom: 1px solid var(--c-border-subtle);">
                        <span class="flex-1 text-sm" style="color: var(--c-text-1);">{{ $line['label'] }}</span>
                        <span class="text-xs shrink-0" style="color: var(--c-text-3);">{{ $line['payFrom'] }}</span>
                        <span class="text-sm font-bold tabular-nums w-28 text-right shrink-0" style="color: var(--c-text-1);">
                            €&thinsp;{{ number_format($line['amount'], 2, ',', '.') }}
                        </span>
                    </div>
                @endforeach

                <div class="flex items-center gap-4 px-6 py-4" style="background-color: var(--c-elevated);">
                    <span class="flex-1 text-sm font-bold" style="color: var(--c-text-1);">Total</span>
                    <span class="text-base font-bold tabular-nums w-28 text-right shrink-0" style="color: var(--c-income);">
                        €&thinsp;{{ number_format($amexSplit['total'], 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </section>
    @endif

    {{-- ── Net Worth ───────────────────────────────────────────────────────── --}}
    <section>
        <h2 class="text-xs font-semibold uppercase tracking-widest mb-4" style="color: var(--c-text-3); letter-spacing: 0.12em;">Net Worth</h2>

        @if ($netWorthAccounts->isEmpty())
            <div class="rounded-2xl px-6 py-12 text-center" style="background-color: var(--c-card); border: 1px solid var(--c-border);">
                <p class="text-sm" style="color: var(--c-text-3);">No net worth accounts configured.</p>
                <a href="{{ route('settings') }}" class="th-link text-sm mt-2 inline-block">Add accounts in Settings →</a>
            </div>
        @else
            <div class="rounded-2xl overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border);">
                @foreach ($netWorthAccounts as $index => $nwAccount)
                    @php
                        $latestSnapshot = $nwAccount->latestSnapshot;
                        $balance = (float) ($latestSnapshot?->balance ?? 0);
                        $isLast = $index === $netWorthAccounts->count() - 1;
                        $typeColorVar = match ($nwAccount->type) {
                            'savings'    => 'var(--c-income)',
                            'investment' => 'var(--c-revolut-text)',
                            default      => 'var(--c-text-2)',
                        };
                        $typeBgVar = match ($nwAccount->type) {
                            'savings'    => 'var(--c-income-bg)',
                            'investment' => 'var(--c-revolut-bg)',
                            default      => 'var(--c-hover)',
                        };
                    @endphp

                    <div class="flex items-center gap-4 px-6 py-4" style="{{ $isLast ? '' : 'border-bottom: 1px solid var(--c-border-subtle);' }}"
                         x-data="{ editing: false, newBalance: {{ $balance }} }">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold" style="color: var(--c-text-1);">{{ $nwAccount->name }}</span>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="color: {{ $typeColorVar }}; background-color: {{ $typeBgVar }};">
                                    {{ ucfirst($nwAccount->type) }}
                                </span>
                            </div>
                            @if ($latestSnapshot?->recorded_at)
                                <span class="text-xs mt-0.5 block" style="color: var(--c-text-3);">
                                    Updated {{ $latestSnapshot->recorded_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-xs mt-0.5 block" style="color: var(--c-text-3);">No balance recorded yet</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <span x-show="!editing" class="text-base font-bold tabular-nums" style="color: var(--c-text-1);">
                                €&thinsp;{{ number_format($balance, 2, ',', '.') }}
                            </span>

                            <div x-show="editing" x-cloak class="flex items-center gap-2">
                                <div class="flex">
                                    <span class="flex items-center px-2.5 text-sm rounded-l-lg select-none" style="color: var(--c-text-2); background-color: var(--c-deep); border: 1px solid var(--c-border-medium); border-right: none;">€</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        x-model="newBalance"
                                        x-ref="balanceInput"
                                        @keydown.enter="$wire.updateNetWorthBalance({{ $nwAccount->id }}, String(newBalance)); editing = false"
                                        @keydown.escape="editing = false"
                                        class="th-input w-32 rounded-r-lg px-2.5 py-1.5 text-sm"
                                    >
                                </div>
                                <button @click="$wire.updateNetWorthBalance({{ $nwAccount->id }}, String(newBalance)); editing = false"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-lg text-white transition-all"
                                        style="background: linear-gradient(135deg, #7C6FF7, #5B4FD4);">Save</button>
                                <button @click="editing = false"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
                                        style="background-color: var(--c-elevated); color: var(--c-text-2);">Cancel</button>
                            </div>

                            <button x-show="!editing"
                                    @click="editing = true; $nextTick(() => $refs.balanceInput.focus())"
                                    class="th-btn th-btn-brand">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach

                <div class="flex items-center justify-between px-6 py-5" style="background-color: var(--c-elevated); border-top: 1px solid var(--c-border-subtle);">
                    <span class="text-sm font-semibold" style="color: var(--c-text-2);">Total Net Worth</span>
                    <span class="text-2xl font-bold tabular-nums" style="color: var({{ $netWorthTotal >= 0 ? '--c-text-1' : '--c-expense' }});">
                        €&thinsp;{{ number_format($netWorthTotal, 2, ',', '.') }}
                    </span>
                </div>
            </div>
        @endif
    </section>

    {{-- ── AmEx Paid Modal ─────────────────────────────────────────────────── --}}
    @if ($showAmexModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-data x-on:keydown.escape.window="$wire.closeAmexModal()">
            <div class="absolute inset-0 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.6);" wire:click="closeAmexModal"></div>

            <div class="relative w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border-medium);">
                <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid var(--c-border);">
                    <h2 class="text-base font-bold" style="color: var(--c-text-1);">Mark AmEx as paid</h2>
                    <button wire:click="closeAmexModal" class="th-btn th-btn-ghost">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-widest mb-2" style="color: var(--c-text-3);">Payment date</label>
                        <input type="date" wire:model="amexPayDate"
                               class="th-input w-full rounded-xl px-3 py-2.5 text-sm">
                        @error('amexPayDate')
                            <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p>
                        @enderror
                    </div>
                    <p class="text-xs" style="color: var(--c-text-3);">
                        Total to pay:
                        <span class="font-bold" style="color: var(--c-text-1);">€&thinsp;{{ number_format($amexSplit['total'], 2, ',', '.') }}</span>
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4" style="border-top: 1px solid var(--c-border); background-color: var(--c-elevated);">
                    <button wire:click="closeAmexModal" class="px-4 py-2 text-sm font-medium th-link">Cancel</button>
                    <button wire:click="markAmexPaid"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-70 cursor-not-allowed"
                            class="px-5 py-2 text-sm font-semibold rounded-xl text-white transition-colors"
                            style="background-color: var(--c-income);">
                        <span wire:loading.remove wire:target="markAmexPaid">Confirm payment</span>
                        <span wire:loading wire:target="markAmexPaid">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
