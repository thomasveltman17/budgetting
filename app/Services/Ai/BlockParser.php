<?php

namespace App\Services\Ai;

use App\Models\Transaction;

/**
 * Turns the model's final answer into display blocks the panel knows how to
 * render. Anything unexpected is dropped or capped, so a malformed answer can
 * never break the page. Transaction lists are filled in from the database by
 * id, so they always show real rows.
 */
class BlockParser
{
    public const FORMATS = ['money', 'percent', 'number', 'text'];

    private const TONES = ['insight', 'tip', 'warning', 'positive'];

    /**
     * @return list<array<string, mixed>>
     */
    public function parse(?string $answer): array
    {
        $answer = trim((string) $answer);

        if ($answer === '') {
            return [['type' => 'text', 'text' => 'I could not come up with an answer to that. Try asking it differently.']];
        }

        $decoded = $this->decode($answer);

        if ($decoded === null) {
            return [['type' => 'text', 'text' => $this->limit($answer, 4000)]];
        }

        $blocks = array_values(array_filter(array_map(
            fn ($block) => is_array($block) ? $this->sanitize($block) : null,
            array_slice($decoded, 0, 12),
        )));

        return $blocks === []
            ? [['type' => 'text', 'text' => 'I could not come up with an answer to that. Try asking it differently.']]
            : $blocks;
    }

    /**
     * Pull the block list out of the answer, tolerating code fences and
     * stray text around the JSON.
     *
     * @return list<mixed>|null
     */
    private function decode(string $answer): ?array
    {
        $candidates = [$answer];

        if (preg_match('/```(?:json)?\s*(.+?)```/s', $answer, $fenced)) {
            $candidates[] = $fenced[1];
        }

        $start = strpos($answer, '{');
        $end = strrpos($answer, '}');

        if ($start !== false && $end > $start) {
            $candidates[] = substr($answer, $start, $end - $start + 1);
        }

        foreach ($candidates as $candidate) {
            $json = json_decode(trim($candidate), true);

            if (is_array($json) && isset($json['blocks']) && is_array($json['blocks'])) {
                return array_values($json['blocks']);
            }

            if (is_array($json) && array_is_list($json)) {
                return $json;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function sanitize(array $block): ?array
    {
        return match ($block['type'] ?? null) {
            'text' => $this->textBlock($block),
            'callout' => $this->calloutBlock($block),
            'stats' => $this->statsBlock($block),
            'bar_chart', 'donut_chart' => $this->seriesBlock($block),
            'line_chart' => $this->lineBlock($block),
            'table' => $this->tableBlock($block),
            'transactions' => $this->transactionsBlock($block),
            'suggestions' => $this->suggestionsBlock($block),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function textBlock(array $block): ?array
    {
        $text = $this->string($block['text'] ?? null, 4000);

        return $text === null ? null : ['type' => 'text', 'text' => $text];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function calloutBlock(array $block): ?array
    {
        $text = $this->string($block['text'] ?? null, 1000);

        if ($text === null) {
            return null;
        }

        return [
            'type' => 'callout',
            'tone' => in_array($block['tone'] ?? null, self::TONES, true) ? $block['tone'] : 'insight',
            'title' => $this->string($block['title'] ?? null, 120),
            'text' => $text,
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function statsBlock(array $block): ?array
    {
        $items = collect($this->list($block['items'] ?? null))
            ->take(6)
            ->map(function ($item) {
                if (! is_array($item) || ($label = $this->string($item['label'] ?? null, 60)) === null) {
                    return null;
                }

                $format = $this->format($item['format'] ?? null);
                $value = $item['value'] ?? null;

                if ($format === 'text' ? ! is_scalar($value) : ! is_numeric($value)) {
                    return null;
                }

                return [
                    'label' => $label,
                    'value' => $format === 'text' ? $this->limit((string) $value, 40) : (float) $value,
                    'format' => $format,
                    'caption' => $this->string($item['caption'] ?? null, 80),
                    'tone' => in_array($item['tone'] ?? null, ['positive', 'negative', 'neutral'], true) ? $item['tone'] : 'neutral',
                ];
            })
            ->filter()
            ->values()
            ->all();

        return $items === [] ? null : ['type' => 'stats', 'items' => $items];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function seriesBlock(array $block): ?array
    {
        $max = $block['type'] === 'donut_chart' ? 8 : 15;

        $items = collect($this->list($block['items'] ?? null))
            ->filter(fn ($item) => is_array($item) && is_numeric($item['value'] ?? null) && $this->string($item['label'] ?? null, 60) !== null)
            ->take($max)
            ->map(fn (array $item) => [
                'label' => $this->string($item['label'], 60),
                'value' => (float) $item['value'],
                'color' => $this->color($item['color'] ?? null),
                'caption' => $this->string($item['caption'] ?? null, 80),
            ])
            ->values()
            ->all();

        if ($items === []) {
            return null;
        }

        return [
            'type' => $block['type'],
            'title' => $this->string($block['title'] ?? null, 120),
            'format' => $this->format($block['format'] ?? 'money'),
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function lineBlock(array $block): ?array
    {
        $labels = collect($this->list($block['labels'] ?? null))
            ->take(24)
            ->map(fn ($label) => is_scalar($label) ? $this->limit((string) $label, 24) : '')
            ->values()
            ->all();

        if (count($labels) < 2) {
            return null;
        }

        $series = collect($this->list($block['series'] ?? null))
            ->filter(fn ($series) => is_array($series) && is_array($series['values'] ?? null))
            ->take(3)
            ->map(fn (array $series) => [
                'name' => $this->string($series['name'] ?? null, 40) ?? 'Series',
                'color' => $this->color($series['color'] ?? null),
                'values' => array_map(
                    fn ($value) => is_numeric($value) ? (float) $value : 0.0,
                    array_pad(array_slice(array_values($series['values']), 0, count($labels)), count($labels), 0),
                ),
            ])
            ->values()
            ->all();

        if ($series === []) {
            return null;
        }

        return [
            'type' => 'line_chart',
            'title' => $this->string($block['title'] ?? null, 120),
            'format' => $this->format($block['format'] ?? 'money'),
            'labels' => $labels,
            'series' => $series,
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function tableBlock(array $block): ?array
    {
        $columns = collect($this->list($block['columns'] ?? null))
            ->filter(fn ($column) => is_array($column) && $this->string($column['key'] ?? null, 40) !== null)
            ->take(6)
            ->map(fn (array $column) => [
                'key' => $this->string($column['key'], 40),
                'label' => $this->string($column['label'] ?? null, 40) ?? $this->string($column['key'], 40),
                'format' => $this->format($column['format'] ?? 'text'),
            ])
            ->values()
            ->all();

        if ($columns === []) {
            return null;
        }

        $rows = collect($this->list($block['rows'] ?? null))
            ->filter(fn ($row) => is_array($row))
            ->take(25)
            ->map(fn (array $row) => collect($columns)->mapWithKeys(function (array $column) use ($row) {
                $value = $row[$column['key']] ?? null;

                return [$column['key'] => match (true) {
                    $column['format'] !== 'text' && is_numeric($value) => (float) $value,
                    is_scalar($value) => $this->limit((string) $value, 120),
                    default => null,
                }];
            })->all())
            ->values()
            ->all();

        if ($rows === []) {
            return null;
        }

        return [
            'type' => 'table',
            'title' => $this->string($block['title'] ?? null, 120),
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function transactionsBlock(array $block): ?array
    {
        $ids = collect($this->list($block['ids'] ?? null))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(25)
            ->values();

        if ($ids->isEmpty()) {
            return null;
        }

        $transactions = Transaction::with(['account', 'category'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $rows = $ids
            ->map(fn (int $id) => $transactions->get($id))
            ->filter()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'date' => $transaction->date->toDateString(),
                'merchant' => $transaction->merchant_name,
                'description' => $transaction->description,
                'account' => $transaction->account?->label,
                'category' => $transaction->category?->name,
                'color' => $transaction->category?->color,
                'amount' => (float) $transaction->amount,
                'notes' => $transaction->notes,
            ])
            ->values()
            ->all();

        if ($rows === []) {
            return null;
        }

        return [
            'type' => 'transactions',
            'title' => $this->string($block['title'] ?? null, 120),
            'transactions' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function suggestionsBlock(array $block): ?array
    {
        $items = collect($this->list($block['items'] ?? null))
            ->map(fn ($item) => $this->string($item, 120))
            ->filter()
            ->unique()
            ->take(4)
            ->values()
            ->all();

        return $items === [] ? null : ['type' => 'suggestions', 'items' => $items];
    }

    /**
     * @return list<mixed>
     */
    private function list(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    private function string(mixed $value, int $max): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $this->limit($value, $max);
    }

    private function limit(string $value, int $max): string
    {
        return mb_strlen($value) > $max ? rtrim(mb_substr($value, 0, $max - 1)).'…' : $value;
    }

    private function format(mixed $format): string
    {
        return in_array($format, self::FORMATS, true) ? $format : 'number';
    }

    private function color(mixed $color): ?string
    {
        return is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : null;
    }
}
