@props(['block'])
@php
    use App\Services\Ai\ValueFormatter;
    use Illuminate\Support\Str;

    $palette = ['#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#06b6d4', '#a855f7', '#84cc16', '#f97316', '#ec4899', '#14b8a6'];
    $markdown = fn (string $text) => Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    $format = $block['format'] ?? 'number';
@endphp

@switch($block['type'])
    @case('text')
        <div class="assistant-prose text-sm leading-relaxed text-ink">{!! $markdown($block['text']) !!}</div>
        @break

    @case('callout')
        @php
            $tone = [
                'insight' => ['border-accent/30 bg-accent-soft', 'bg-accent'],
                'tip' => ['border-attention/30 bg-attention-soft', 'bg-attention'],
                'warning' => ['border-over/30 bg-over-soft', 'bg-over'],
                'positive' => ['border-credit/30 bg-credit/10', 'bg-credit'],
            ][$block['tone']];
        @endphp
        <div class="flex gap-3 rounded-lg border px-3.5 py-3 {{ $tone[0] }}">
            <span class="mt-1.5 size-1.5 shrink-0 rounded-full {{ $tone[1] }}"></span>
            <div class="min-w-0 text-sm">
                @if ($block['title'])<p class="font-medium text-ink">{{ $block['title'] }}</p>@endif
                <div class="assistant-prose text-ink-2 {{ $block['title'] ? 'mt-0.5' : '' }}">{!! $markdown($block['text']) !!}</div>
            </div>
        </div>
        @break

    @case('stats')
        <div class="grid grid-cols-2 gap-2">
            @foreach ($block['items'] as $item)
                <div class="rounded-lg border border-line bg-surface px-3 py-2.5">
                    <p class="text-xs text-ink-3">{{ $item['label'] }}</p>
                    <p class="mt-1 text-lg font-semibold tracking-tight text-ink tabular-nums">{{ ValueFormatter::format($item['value'], $item['format']) }}</p>
                    @if ($item['caption'])
                        <p class="mt-0.5 text-xs {{ ['positive' => 'text-credit', 'negative' => 'text-over', 'neutral' => 'text-ink-3'][$item['tone']] }}">{{ $item['caption'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
        @break

    @case('bar_chart')
        @php $max = max(array_map(fn ($item) => abs($item['value']), $block['items'])) ?: 1; @endphp
        <figure class="rounded-lg border border-line bg-surface px-3.5 py-3">
            @if ($block['title'])<figcaption class="mb-3 text-xs font-medium text-ink-2">{{ $block['title'] }}</figcaption>@endif
            <ul class="space-y-2.5">
                @foreach ($block['items'] as $item)
                    <li>
                        <div class="flex items-baseline justify-between gap-3 text-[0.8125rem]">
                            <span class="truncate text-ink">{{ $item['label'] }}</span>
                            <span class="shrink-0 tabular-nums text-ink-2">{{ ValueFormatter::format($item['value'], $format) }}</span>
                        </div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-sunken">
                            <div class="h-full rounded-full" style="width: {{ round(abs($item['value']) / $max * 100, 1) }}%; background-color: {{ $item['color'] ?? $palette[$loop->index % count($palette)] }}"></div>
                        </div>
                        @if ($item['caption'])<p class="mt-0.5 text-xs text-ink-3">{{ $item['caption'] }}</p>@endif
                    </li>
                @endforeach
            </ul>
        </figure>
        @break

    @case('donut_chart')
        @php
            $total = array_sum(array_map(fn ($item) => max(0, $item['value']), $block['items'])) ?: 1;
            $circumference = 2 * M_PI * 15.9155;
            $offset = 0;
        @endphp
        <figure class="rounded-lg border border-line bg-surface px-3.5 py-3">
            @if ($block['title'])<figcaption class="mb-3 text-xs font-medium text-ink-2">{{ $block['title'] }}</figcaption>@endif
            <div class="flex items-center gap-4">
                <svg viewBox="0 0 42 42" class="size-28 shrink-0 -rotate-90" role="img" aria-label="{{ $block['title'] ?? 'Breakdown' }}">
                    <circle cx="21" cy="21" r="15.9155" fill="none" class="stroke-sunken" stroke-width="5"></circle>
                    @foreach ($block['items'] as $item)
                        @php $length = max(0, $item['value']) / $total * $circumference; @endphp
                        <circle cx="21" cy="21" r="15.9155" fill="none" stroke-width="5"
                                stroke="{{ $item['color'] ?? $palette[$loop->index % count($palette)] }}"
                                stroke-dasharray="{{ round($length, 3) }} {{ round($circumference - $length, 3) }}"
                                stroke-dashoffset="{{ round(-$offset, 3) }}">
                            <title>{{ $item['label'] }}: {{ ValueFormatter::format($item['value'], $format) }}</title>
                        </circle>
                        @php $offset += $length; @endphp
                    @endforeach
                </svg>
                <ul class="min-w-0 flex-1 space-y-1.5 text-[0.8125rem]">
                    @foreach ($block['items'] as $item)
                        <li class="flex items-center gap-2">
                            <span class="size-2 shrink-0 rounded-[2px]" style="background-color: {{ $item['color'] ?? $palette[$loop->index % count($palette)] }}"></span>
                            <span class="min-w-0 flex-1 truncate text-ink">{{ $item['label'] }}</span>
                            <span class="shrink-0 tabular-nums text-ink-2">{{ ValueFormatter::format($item['value'], $format) }}</span>
                            <span class="w-9 shrink-0 text-right font-mono text-xs tabular-nums text-ink-3">{{ round(max(0, $item['value']) / $total * 100) }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </figure>
        @break

    @case('line_chart')
        @php
            $values = array_merge(...array_map(fn ($series) => $series['values'], $block['series']));
            $high = max(max($values), 0);
            $low = min(min($values), 0);
            $range = ($high - $low) ?: 1;
            $count = count($block['labels']);
            $width = 300;
            $height = 110;
            $x = fn (int $index) => $count > 1 ? round($index / ($count - 1) * $width, 2) : 0;
            $y = fn (float $value) => round($height - (($value - $low) / $range * ($height - 8)) - 4, 2);
            $labelStep = max(1, (int) ceil($count / 6));
        @endphp
        <figure class="rounded-lg border border-line bg-surface px-3.5 py-3">
            <div class="mb-3 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                @if ($block['title'])<figcaption class="text-xs font-medium text-ink-2">{{ $block['title'] }}</figcaption>@endif
                <div class="flex flex-wrap gap-3 text-xs text-ink-3">
                    @foreach ($block['series'] as $series)
                        <span class="flex items-center gap-1.5"><span class="h-0.5 w-3 rounded-full" style="background-color: {{ $series['color'] ?? $palette[$loop->index] }}"></span>{{ $series['name'] }}</span>
                    @endforeach
                </div>
            </div>
            <svg viewBox="-4 0 {{ $width + 8 }} {{ $height }}" class="h-28 w-full overflow-visible" preserveAspectRatio="none" role="img" aria-label="{{ $block['title'] ?? 'Trend' }}">
                <line x1="0" x2="{{ $width }}" y1="{{ $y(0) }}" y2="{{ $y(0) }}" class="stroke-line" stroke-width="1" vector-effect="non-scaling-stroke"></line>
                @foreach ($block['series'] as $series)
                    @php $color = $series['color'] ?? $palette[$loop->index]; @endphp
                    <polyline fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"
                              points="{{ collect($series['values'])->map(fn ($value, $index) => $x($index).','.$y($value))->implode(' ') }}"></polyline>
                    @foreach ($series['values'] as $index => $value)
                        <circle cx="{{ $x($index) }}" cy="{{ $y($value) }}" r="2.5" fill="{{ $color }}" vector-effect="non-scaling-stroke">
                            <title>{{ $series['name'] }}, {{ $block['labels'][$index] }}: {{ ValueFormatter::format($value, $format) }}</title>
                        </circle>
                    @endforeach
                @endforeach
            </svg>
            <div class="mt-1.5 flex justify-between text-[0.6875rem] text-ink-3">
                @foreach ($block['labels'] as $index => $label)
                    @if ($index % $labelStep === 0 || $index === $count - 1)<span>{{ $label }}</span>@endif
                @endforeach
            </div>
        </figure>
        @break

    @case('table')
        <figure class="overflow-hidden rounded-lg border border-line bg-surface">
            @if ($block['title'])<figcaption class="border-b border-line px-3.5 py-2 text-xs font-medium text-ink-2">{{ $block['title'] }}</figcaption>@endif
            <div class="overflow-x-auto">
                <table class="w-full text-[0.8125rem]">
                    <thead>
                        <tr class="border-b border-line text-xs text-ink-3">
                            @foreach ($block['columns'] as $column)
                                <th class="px-3.5 py-2 font-medium {{ $column['format'] === 'text' ? 'text-left' : 'text-right' }}">{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($block['rows'] as $row)
                            <tr>
                                @foreach ($block['columns'] as $column)
                                    <td class="px-3.5 py-2 {{ $column['format'] === 'text' ? 'text-left text-ink' : 'text-right tabular-nums text-ink-2' }}">{{ ValueFormatter::format($row[$column['key']], $column['format']) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </figure>
        @break

    @case('transactions')
        <figure class="overflow-hidden rounded-lg border border-line bg-surface">
            @if ($block['title'])<figcaption class="border-b border-line px-3.5 py-2 text-xs font-medium text-ink-2">{{ $block['title'] }}</figcaption>@endif
            <ul class="divide-y divide-line">
                @foreach ($block['transactions'] as $transaction)
                    <li>
                        <a href="{{ route('transactions', ['search' => $transaction['description']]) }}" class="flex items-center gap-3 px-3.5 py-2.5 hover:bg-sunken/50" title="{{ $transaction['description'] }}">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[0.8125rem] font-medium text-ink">{{ $transaction['merchant'] }}</p>
                                <p class="mt-0.5 flex min-w-0 items-center gap-1.5 truncate text-xs text-ink-3">
                                    <span class="size-1.5 shrink-0 rounded-[2px] {{ $transaction['color'] ? '' : 'ring-1 ring-attention ring-inset' }}" @if ($transaction['color']) style="background-color: {{ $transaction['color'] }}" @endif></span>
                                    <span class="truncate">{{ \Illuminate\Support\Carbon::parse($transaction['date'])->format('j M') }} · {{ $transaction['account'] }} · {{ $transaction['category'] ?? 'Uncategorized' }}@if ($transaction['notes']) · {{ $transaction['notes'] }}@endif</span>
                                </p>
                            </div>
                            <x-money :amount="$transaction['amount']" signed class="shrink-0 text-[0.8125rem] font-medium tabular-nums {{ $transaction['amount'] > 0 ? 'text-credit' : 'text-ink' }}" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </figure>
        @break

    @case('suggestions')
        <div class="flex flex-wrap gap-1.5">
            @foreach ($block['items'] as $item)
                <button type="button" @click="send(@js($item))" :disabled="pending !== null"
                        class="rounded-full border border-line bg-surface px-3 py-1 text-left text-xs text-ink-2 transition-colors hover:border-accent/50 hover:text-ink disabled:opacity-50">
                    {{ $item }}
                </button>
            @endforeach
        </div>
        @break
@endswitch
