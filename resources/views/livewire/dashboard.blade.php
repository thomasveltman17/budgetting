@php
    $periodLength = $period->lengthInDays();
    $elapsedDays = $period->elapsedDays();
    $isRunning = $elapsedDays > 0 && $elapsedDays < $periodLength;
    $paceFraction = $isRunning ? $elapsedDays / $periodLength : null;
    $today = today();

    $euro = fn (float $amount): string => "€\u{00A0}".number_format($amount, 2, ',', '.');

    // Scale the ruler to ordinary days; outliers (rent, a big transfer) are
    // drawn at full height with a break mark instead of flattening the rest.
    $spendingDays = $dailyActivity->pluck('spent')->filter(fn ($spent) => $spent > 0)->sort()->values();
    $typicalHighDay = $spendingDays->isEmpty() ? 0 : (float) $spendingDays->get((int) floor(($spendingDays->count() - 1) * 0.8));
    $rulerScale = max(1, min((float) $spendingDays->last(), $typicalHighDay * 2.5));
    $peakDay = $dailyActivity->sortByDesc('spent')->first();
    $daysCounted = $isRunning ? $elapsedDays : $periodLength;
    $averagePerDay = $daysCounted > 0 ? $periodOverview['expenses'] / $daysCounted : 0;

    $rulerDays = $dailyActivity->map(fn ($day) => [
        'label' => $day['date']->format('D j M'),
        'spent' => $euro($day['spent']),
        'received' => $day['received'] > 0 ? $euro($day['received']) : null,
    ])->all();

    $net = $periodOverview['net'];
@endphp

<div class="mx-auto max-w-[1180px] pb-16">

    <x-page-header title="Dashboard">
        <x-slot:meta>
            {{ $period->start_date->format('j F') }} – {{ $period->end_date->format('j F Y') }}
            <span class="px-1 text-line-strong">·</span>
            @if ($elapsedDays === 0)
                Starts {{ $period->start_date->format('j M') }}
            @elseif ($isRunning)
                Day {{ $elapsedDays }} of {{ $periodLength }}
            @else
                Closed
            @endif
        </x-slot:meta>
    </x-page-header>

    <div class="space-y-6 px-5 sm:px-8">

        {{-- ── Uncategorized notice ───────────────────────────────────────── --}}
        @if ($uncategorizedCount > 0)
            <a href="{{ route('transactions') }}?uncategorizedOnly=1"
               class="group flex items-center justify-between gap-4 rounded-lg border border-attention/25 bg-attention-soft px-4 py-2.5 text-sm text-attention transition-colors hover:border-attention/50">
                <span class="flex items-center gap-2.5">
                    <span class="size-1.5 shrink-0 rounded-full bg-attention"></span>
                    {{ $uncategorizedCount }} {{ $uncategorizedCount === 1 ? 'expense has' : 'expenses have' }} no category yet
                </span>
                <span class="shrink-0 font-medium">Categorize <span class="inline-block transition-transform group-hover:translate-x-0.5">→</span></span>
            </a>
        @endif

        {{-- ── Period: totals + day ruler ─────────────────────────────────── --}}
        <section class="rounded-xl border border-line bg-surface" aria-labelledby="period-heading">
            <h2 id="period-heading" class="sr-only">This period</h2>

            <div class="grid grid-cols-2 gap-x-8 gap-y-6 px-5 pt-6 sm:px-7 md:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)] md:items-end">
                <div class="col-span-2 md:col-span-1">
                    <p class="text-sm text-ink-2">Spent this period</p>
                    <p class="mt-2 text-[2.5rem] leading-none font-semibold tracking-[-0.03em] text-ink sm:text-5xl">
                        <span class="mr-1.5 align-[0.1em] text-[0.55em] font-normal tracking-normal text-ink-3">€</span>{{ number_format($periodOverview['expenses'], 2, ',', '.') }}
                    </p>
                </div>
                <div class="border-l border-line pl-5 md:pl-6">
                    <p class="text-sm text-ink-2">Money in</p>
                    <p class="mt-2 text-xl font-medium tracking-[-0.01em] text-ink">
                        <x-money :amount="$periodOverview['income']" />
                    </p>
                </div>
                <div class="border-l border-line pl-5 md:pl-6">
                    <p class="text-sm text-ink-2">Net</p>
                    <p class="mt-2 text-xl font-medium tracking-[-0.01em] {{ $net < 0 ? 'text-over' : 'text-ink' }}">
                        <x-money :amount="$net" signed />
                    </p>
                </div>
            </div>

            {{-- Day ruler: one column per day of the period --}}
            <div class="mt-8 px-5 pb-5 sm:px-7 sm:pb-6" x-data="{ hover: null, days: @js($rulerDays) }">

                <div class="mb-3 flex min-h-5 flex-wrap items-baseline justify-between gap-x-6 gap-y-1 text-[0.8125rem]">
                    <p class="text-ink-2" aria-live="polite">
                        <template x-if="hover !== null">
                            <span>
                                <span class="font-medium text-ink" x-text="days[hover].label"></span>
                                <span class="px-1 text-ink-3">·</span>spent <span class="tabular-nums text-ink" x-text="days[hover].spent"></span>
                                <template x-if="days[hover].received">
                                    <span><span class="px-1 text-ink-3">·</span>in <span class="tabular-nums text-credit" x-text="days[hover].received"></span></span>
                                </template>
                            </span>
                        </template>
                        <span x-show="hover === null">
                            @if ($peakDay && $peakDay['spent'] > 0)
                                Highest day {{ $peakDay['date']->format('D j M') }}, {{ $euro($peakDay['spent']) }}
                                <span class="px-1 text-ink-3">·</span>{{ $euro($averagePerDay) }} per day on average
                            @else
                                No spending recorded yet
                            @endif
                        </span>
                    </p>
                    <p class="flex flex-wrap items-center gap-x-4 gap-y-1 text-ink-3">
                        <span class="flex items-center gap-1.5 whitespace-nowrap"><span class="h-2.5 w-1.5 rounded-t-[2px] bg-bar"></span>Spent</span>
                        <span class="flex items-center gap-1.5 whitespace-nowrap"><span class="h-1 w-2 rounded-b-[2px] bg-credit"></span>Money in</span>
                        @if ($spendingDays->last() > $rulerScale)
                            <span class="flex items-center gap-1.5 whitespace-nowrap" title="Days above this amount are cut short; hover them for the full amount">
                                <span class="relative h-2.5 w-1.5 overflow-hidden rounded-t-[2px] bg-bar"><span class="absolute inset-x-[-1px] top-[3px] h-[2px] -skew-y-[24deg] bg-surface"></span></span>Above {{ $euro($rulerScale) }}
                            </span>
                        @endif
                    </p>
                </div>

                <div class="flex gap-[2px]" @mouseleave="hover = null">
                    @foreach ($dailyActivity as $index => $day)
                        @php
                            $isToday = $day['date']->isSameDay($today);
                            $isFuture = $day['date']->gt($today);
                            $barHeight = $day['spent'] > 0 ? max(3, min(100, round($day['spent'] / $rulerScale * 100, 2))) : 0;
                            $isOffScale = $day['spent'] > $rulerScale;
                            $isKeyLabel = $index === 0 || $day['date']->day === 1 || $isToday || $loop->last || $day['date']->day % 5 === 0;
                            $showMonth = $index === 0 || $day['date']->day === 1;
                        @endphp
                        <div class="group flex min-w-0 flex-1 flex-col items-center" @mouseenter="hover = {{ $index }}">
                            {{-- bar --}}
                            <div class="flex h-24 w-full items-end justify-center">
                                @if ($barHeight > 0)
                                    <div class="relative w-full max-w-[18px] rounded-t-[3px] transition-colors {{ $isToday ? 'bg-accent' : 'bg-bar group-hover:bg-ink-2' }}"
                                         style="height: {{ $barHeight }}%">
                                        @if ($isOffScale)
                                            <span class="absolute inset-x-[-1px] top-2.5 h-[3px] -skew-y-[24deg] bg-surface" aria-hidden="true"></span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            {{-- baseline --}}
                            <div class="h-px w-full {{ $isToday ? 'bg-accent' : ($isFuture ? 'bg-line' : 'bg-line-strong') }}"></div>
                            {{-- money in --}}
                            <div class="flex h-2 w-full justify-center">
                                @if ($day['received'] > 0)
                                    <div class="mt-px h-1 w-full max-w-[18px] rounded-b-[2px] bg-credit"></div>
                                @endif
                            </div>
                            {{-- day label --}}
                            <div class="relative mt-1 h-8 w-full text-center font-mono text-[0.625rem] leading-3 tabular-nums {{ $isToday ? 'font-medium text-accent' : ($isFuture ? 'text-ink-3/60' : 'text-ink-3') }}">
                                <span class="{{ $isKeyLabel ? '' : 'hidden md:inline' }}">{{ $day['date']->day }}</span>
                                @if ($showMonth)
                                    <span class="absolute top-4 left-1/2 -translate-x-1/2 text-ink-2">{{ $day['date']->format('M') }}</span>
                                @elseif ($isToday)
                                    <span class="absolute top-4 left-1/2 -translate-x-1/2">today</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="grid grid-cols-[minmax(0,1fr)] gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] lg:items-start">

            {{-- ── Budget ──────────────────────────────────────────────────── --}}
            <section class="rounded-xl border border-line bg-surface" aria-labelledby="budget-heading">
                <header class="flex items-baseline justify-between gap-4 border-b border-line px-5 py-3.5">
                    <h2 id="budget-heading" class="text-[0.9375rem] font-semibold">Budget</h2>
                    <a href="{{ route('settings') }}#budget-targets" class="text-[0.8125rem] text-ink-3 transition-colors hover:text-ink">Edit targets</a>
                </header>

                <ul class="divide-y divide-line">
                    @foreach ($categoryProgress as $row)
                        @php
                            $isGoalCategory = in_array($row['category']->type, ['savings', 'investment']);
                            $percentage = $row['percentage'];
                            $hasTarget = $row['target'] !== null;
                            $isOver = $percentage !== null && $percentage > 100;
                            $fillClass = match (true) {
                                $isOver && $isGoalCategory => 'bg-credit',
                                $isOver => 'bg-over',
                                $percentage !== null && $percentage > 80 && ! $isGoalCategory => 'bg-attention',
                                default => 'bg-ink-2',
                            };
                            $isIdle = ! $hasTarget && $row['spent'] == 0;
                        @endphp
                        <li class="px-5 py-3.5 {{ $isIdle ? 'text-ink-3' : '' }}">
                            <div class="flex items-baseline gap-3">
                                <span class="size-2 shrink-0 -translate-y-px rounded-[2px]" style="background-color: {{ $row['category']->color }}"></span>
                                <span class="min-w-0 flex-1 truncate text-sm {{ $isIdle ? '' : 'text-ink' }}">{{ $row['category']->name }}</span>
                                <span class="shrink-0 text-sm tabular-nums {{ $isIdle ? '' : 'font-medium text-ink' }}"><x-money :amount="$row['spent']" /></span>
                                @if ($hasTarget)
                                    <span class="hidden shrink-0 text-[0.8125rem] text-ink-3 tabular-nums sm:inline">of <x-money :amount="$row['target']" /></span>
                                    <span class="w-11 shrink-0 text-right font-mono text-xs tabular-nums {{ $isOver ? ($isGoalCategory ? 'text-credit' : 'text-over') : 'text-ink-3' }}">{{ round($percentage) }}%</span>
                                @else
                                    <a href="{{ route('settings') }}#budget-targets" class="w-[4.5rem] shrink-0 text-right text-[0.8125rem] text-ink-3 transition-colors hover:text-accent sm:w-auto">Set target</a>
                                @endif
                            </div>

                            @if ($hasTarget)
                                <div class="relative mt-2.5 ml-5 h-1 rounded-full bg-sunken">
                                    <div class="h-full rounded-full {{ $fillClass }}" style="width: {{ min(100, $percentage) }}%"></div>
                                    @if ($paceFraction !== null)
                                        <div class="absolute -top-[3px] -bottom-[3px] w-px bg-ink-3" style="left: {{ round($paceFraction * 100, 2) }}%" title="Today: day {{ $elapsedDays }} of {{ $periodLength }}"></div>
                                    @endif
                                </div>
                                @if ($isOver)
                                    <p class="mt-1.5 ml-5 text-xs {{ $isGoalCategory ? 'text-credit' : 'text-over' }}">
                                        @if ($isGoalCategory)
                                            Target reached
                                        @else
                                            <x-money :amount="$row['spent'] - $row['target']" /> over target
                                        @endif
                                    </p>
                                @endif
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if ($paceFraction !== null)
                    <p class="flex items-center gap-2 border-t border-line px-5 py-2.5 text-xs text-ink-3">
                        <span class="h-2.5 w-px bg-ink-3"></span>
                        Marks today, {{ round($paceFraction * 100) }}% through the period
                    </p>
                @endif
            </section>

            <div class="space-y-6">

                {{-- ── Accounts ────────────────────────────────────────────── --}}
                <section class="rounded-xl border border-line bg-surface" aria-labelledby="accounts-heading">
                    <header class="flex items-baseline justify-between gap-4 border-b border-line px-5 py-3.5">
                        <h2 id="accounts-heading" class="text-[0.9375rem] font-semibold">Spent per account</h2>
                        <span class="text-[0.8125rem] text-ink-3">vs. last period</span>
                    </header>
                    <ul class="divide-y divide-line">
                        @foreach ($accountSummaries as $summary)
                            @php
                                $delta = ($summary['previousSpent'] !== null && $summary['previousSpent'] > 0)
                                    ? round((($summary['totalSpent'] - $summary['previousSpent']) / $summary['previousSpent']) * 100)
                                    : null;
                            @endphp
                            <li class="flex items-center justify-between gap-4 px-5 py-3.5">
                                <div class="flex min-w-0 items-center gap-3">
                                    <x-provider-logo :provider="$summary['account']->name" />
                                    <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-ink">{{ $summary['account']->label }}</p>
                                    <p class="mt-0.5 text-xs text-ink-3">
                                        {{ $summary['transactionCount'] }} {{ Str::plural('transaction', $summary['transactionCount']) }}
                                        @if ($summary['uncategorizedCount'] > 0)
                                            <span class="px-0.5">·</span><span class="text-attention">{{ $summary['uncategorizedCount'] }} uncategorized</span>
                                        @endif
                                    </p>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-medium tabular-nums text-ink"><x-money :amount="$summary['totalSpent']" /></p>
                                    @if ($delta !== null)
                                        <p class="mt-0.5 font-mono text-xs tabular-nums {{ $delta > 0 ? 'text-over' : 'text-credit' }}">
                                            {{ $delta > 0 ? '↑' : '↓' }}&thinsp;{{ abs($delta) }}%
                                        </p>
                                    @else
                                        <p class="mt-0.5 text-xs text-ink-3">—</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>

                {{-- ── AmEx payoff ─────────────────────────────────────────── --}}
                @if ($amexSplit['hasTransactions'])
                    <section class="rounded-xl border border-line bg-surface" aria-labelledby="amex-heading">
                        <header class="flex items-center justify-between gap-4 border-b border-line px-5 py-3">
                            <h2 id="amex-heading" class="flex items-center gap-2.5 text-[0.9375rem] font-semibold">
                                <x-provider-logo provider="amex" size="sm" />
                                AmEx payoff
                            </h2>
                            @if ($period->amex_paid_at)
                                <span class="flex items-center gap-1.5 text-[0.8125rem] text-credit">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    Paid {{ $period->amex_paid_at->format('j M Y') }}
                                </span>
                            @else
                                <button type="button" wire:click="openAmexModal" class="btn btn-secondary btn-sm">Mark as paid</button>
                            @endif
                        </header>

                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-ink-3">
                                    <th class="pt-3 pb-1 pl-5 font-normal">Spent on</th>
                                    <th class="pt-3 pb-1 font-normal">Pay from</th>
                                    <th class="pt-3 pr-5 pb-1 text-right font-normal">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($amexSplit['lines'] as $line)
                                    <tr class="{{ $line['amount'] == 0 ? 'text-ink-3' : '' }}">
                                        <td class="py-3 pl-5 {{ $line['label'] === 'Uncategorized' ? 'text-attention' : '' }}">{{ $line['label'] }}</td>
                                        <td class="py-3 text-[0.8125rem] text-ink-3">
                                            @if ($line['payFrom'] !== '—')
                                                <span class="inline-flex items-center gap-1.5">
                                                    <x-provider-logo :name="$line['payFrom']" size="xs" />
                                                    {{ $line['payFrom'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-5 text-right tabular-nums"><x-money :amount="$line['amount']" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-line-strong">
                                    <td class="py-3 pl-5 font-medium" colspan="2">Total to pay</td>
                                    <td class="py-3 pr-5 text-right font-semibold tabular-nums"><x-money :amount="$amexSplit['total']" /></td>
                                </tr>
                            </tfoot>
                        </table>
                    </section>
                @endif
            </div>
        </div>

        {{-- ── Net worth ───────────────────────────────────────────────────── --}}
        <section class="rounded-xl border border-line bg-surface" aria-labelledby="networth-heading">
            <header class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 border-b border-line px-5 py-4">
                <h2 id="networth-heading" class="text-[0.9375rem] font-semibold">Net worth</h2>
                @if ($netWorthAccounts->isNotEmpty())
                    <p class="text-2xl font-semibold tracking-[-0.02em] {{ $netWorthTotal < 0 ? 'text-over' : 'text-ink' }}">
                        <x-money :amount="$netWorthTotal" />
                    </p>
                @endif
            </header>

            @if ($netWorthAccounts->isEmpty())
                <div class="px-5 py-10 text-center">
                    <p class="text-sm text-ink-2">No savings or investment accounts yet.</p>
                    <a href="{{ route('settings') }}#net-worth" class="mt-2 inline-block text-sm font-medium text-accent hover:underline">Add an account in Settings</a>
                </div>
            @else
                <div class="hidden grid-cols-[minmax(0,1fr)_7rem_9rem_10rem_2rem] gap-4 border-b border-line px-5 py-2 text-xs text-ink-3 md:grid">
                    <span class="pl-11">Account</span>
                    <span>Type</span>
                    <span>Last updated</span>
                    <span class="col-span-2 pr-8 text-right">Balance</span>
                </div>
                <ul class="divide-y divide-line">
                    @foreach ($netWorthAccounts as $nwAccount)
                        @php
                            $latestSnapshot = $nwAccount->latestSnapshot;
                            $balance = (float) ($latestSnapshot?->balance ?? 0);
                        @endphp
                        <li class="relative grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1 px-5 py-3 md:grid-cols-[minmax(0,1fr)_7rem_9rem_10rem_2rem]"
                            x-data="{ editing: false, newBalance: {{ $balance }} }">
                            <span class="flex min-w-0 items-center gap-3">
                                <x-provider-logo :name="$nwAccount->name" />
                                <span class="truncate text-sm font-medium text-ink">{{ $nwAccount->name }}</span>
                            </span>
                            <span class="hidden text-sm text-ink-2 md:block">{{ ucfirst($nwAccount->type) }}</span>
                            <span class="order-last col-span-1 pl-11 text-xs text-ink-3 md:order-none md:pl-0 md:text-sm">
                                <span class="md:hidden">{{ ucfirst($nwAccount->type) }} · </span>{{ $latestSnapshot?->recorded_at ? 'Updated '.$latestSnapshot->recorded_at->diffForHumans() : 'No balance yet' }}
                            </span>

                            <div class="flex items-center justify-end md:col-span-2">
                                <span x-show="!editing" class="mr-1 text-sm font-medium tabular-nums text-ink"><x-money :amount="$balance" /></span>
                                <button x-show="!editing" type="button"
                                        @click="editing = true; $nextTick(() => $refs.balanceInput.select())"
                                        class="icon-btn" title="Update balance">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                </button>

                                <div x-show="editing" x-cloak class="absolute inset-y-0 right-5 flex items-center gap-1.5 bg-surface pl-4">
                                    <div class="relative">
                                        <span class="pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-sm text-ink-3">€</span>
                                        <input type="number" step="0.01" x-model="newBalance" x-ref="balanceInput"
                                               @keydown.enter="$wire.updateNetWorthBalance({{ $nwAccount->id }}, String(newBalance)); editing = false"
                                               @keydown.escape="editing = false"
                                               class="field h-8 w-32 pl-6 text-right tabular-nums"
                                               aria-label="New balance for {{ $nwAccount->name }}">
                                    </div>
                                    <button type="button" @click="$wire.updateNetWorthBalance({{ $nwAccount->id }}, String(newBalance)); editing = false" class="btn btn-primary btn-sm">Save</button>
                                    <button type="button" @click="editing = false" class="btn btn-quiet btn-sm">Cancel</button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- ── Mark AmEx paid ──────────────────────────────────────────────────── --}}
    @if ($showAmexModal)
        <x-modal title="Mark AmEx as paid" close="closeAmexModal" width="max-w-sm">
            <div class="space-y-4 px-5 py-5">
                <div>
                    <x-field-label for="amex-pay-date">Payment date</x-field-label>
                    <input id="amex-pay-date" type="date" wire:model="amexPayDate" class="field">
                    <x-field-error name="amexPayDate" />
                </div>
                <div class="flex items-baseline justify-between rounded-md bg-sunken px-3 py-2.5 text-sm">
                    <span class="text-ink-2">Total to pay</span>
                    <span class="font-semibold tabular-nums"><x-money :amount="$amexSplit['total']" /></span>
                </div>
            </div>
            <x-slot:footer>
                <button type="button" wire:click="closeAmexModal" class="btn btn-quiet">Cancel</button>
                <button type="button" wire:click="markAmexPaid" wire:loading.attr="disabled" class="btn btn-primary">
                    <span wire:loading.remove wire:target="markAmexPaid">Mark as paid</span>
                    <span wire:loading wire:target="markAmexPaid">Saving…</span>
                </button>
            </x-slot:footer>
        </x-modal>
    @endif

</div>
