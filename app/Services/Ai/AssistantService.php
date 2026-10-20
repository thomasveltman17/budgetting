<?php

namespace App\Services\Ai;

use App\Models\Account;
use App\Models\Category;
use App\Services\PeriodService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Answers a question about the user's finances: lets the model look things
 * up with read-only tools, then turns its answer into display blocks.
 */
class AssistantService
{
    public function __construct(
        private AiProviderManager $providers,
        private FinanceTools $tools,
        private BlockParser $parser,
        private PeriodService $periods,
    ) {}

    /**
     * @param  list<array{role: string, content: string}>  $history  earlier questions and answers
     * @return array{blocks: list<array<string, mixed>>, history: list<array{role: string, content: string}>}
     *
     * @throws AiException
     */
    public function ask(string $question, array $history = []): array
    {
        $provider = $this->providers->current();
        $systemPrompt = $this->systemPrompt();
        $messages = [...$history, ['role' => 'user', 'content' => $question]];
        $definitions = $this->tools->definitions();

        $answer = null;

        for ($round = 0; $round <= (int) config('ai.max_tool_rounds', 6); $round++) {
            $isLastRound = $round === (int) config('ai.max_tool_rounds', 6);
            $reply = $provider->generate($systemPrompt, $messages, $isLastRound ? [] : $definitions);

            if (! $reply->wantsTools() || $isLastRound) {
                $answer = $reply->text;

                break;
            }

            $messages[] = ['role' => 'assistant', 'content' => $reply->text, 'tool_calls' => $reply->toolCalls];
            $messages[] = ['role' => 'tool', 'results' => array_map(fn (array $call) => [
                'id' => $call['id'],
                'name' => $call['name'],
                'output' => $this->runTool($call['name'], $call['args']),
            ], $reply->toolCalls)];
        }

        $history = [
            ...$history,
            ['role' => 'user', 'content' => $question],
            ['role' => 'assistant', 'content' => (string) $answer],
        ];

        return [
            'blocks' => $this->parser->parse($answer),
            'history' => array_slice($history, -2 * (int) config('ai.max_history_turns', 8)),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function runTool(string $name, array $args): array
    {
        try {
            return $this->tools->call($name, $args);
        } catch (Throwable $exception) {
            Log::warning('Assistant tool failed', ['tool' => $name, 'args' => $args, 'error' => $exception->getMessage()]);

            return ['error' => 'This lookup failed. Try different arguments or another tool.'];
        }
    }

    public function systemPrompt(): string
    {
        $period = $this->periods->ensureCurrentPeriodExists();
        $today = now()->format('l j F Y');
        $periodLabel = $period->start_date->format('j F').' – '.$period->end_date->format('j F Y');

        $categories = Category::where('is_archived', false)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Category $category) => "- {$category->name} (type: {$category->type})")
            ->implode("\n");

        $accounts = Account::orderBy('label')->pluck('label')->implode(', ');

        return <<<PROMPT
        You are the assistant inside Veltiq Budget, a personal budgeting app used by one person in the Netherlands. You help them understand and improve their spending. Be warm but brief, specific and practical, like a sharp friend who is good with money.

        ## Facts
        - Today is {$today}. Amounts are in euros.
        - Budgets run in periods from the 15th to the 14th of the next month (salary arrives around the 15th). "This month" means the current period: {$periodLabel}. Mention the dates when it matters.
        - Accounts: {$accounts}.
        - Categories:
        {$categories}
        - Category types: "transactional" is everyday spending, "savings" and "investment" are money set aside (not consumption), "income" is income. Uncategorized expenses have no category yet.
        - Expenses are negative amounts. Spending figures from tools are positive and already net of linked repayments (e.g. a friend paying back their share). Transactions marked pending return are excluded from totals.

        ## How to work
        - Always use the tools for numbers. Never guess or invent amounts, dates, merchants or ids. If the data is not there, say so.
        - Call several tools when a good answer needs them (e.g. overview plus categories plus previous period). Keep going until you can answer fully.
        - You can only read data. You cannot change, categorize or delete anything; tell the user where in the app to do it instead (Transactions page, Settings).
        - When asked what to improve, ground every suggestion in their own numbers: name the category or merchant, the amount, the comparison, and a concrete action with the money it would free up.
        - Be proactive: point out something notable you saw in the data even if not asked (an unusual spend, a category over target, many uncategorized transactions, a recurring subscription).
        - Reply in the language the user writes in.

        ## Answer format
        Reply with only a JSON object, no other text, no code fences:
        {"blocks": [ ... ]}

        Block types:
        - {"type": "text", "text": "Markdown: short paragraphs, **bold**, bullet lists."}
        - {"type": "stats", "items": [{"label": "Spent", "value": 1234.5, "format": "money", "caption": "−12% vs last period", "tone": "positive|negative|neutral"}]}  (2 to 4 headline numbers)
        - {"type": "bar_chart", "title": "...", "format": "money", "items": [{"label": "Groceries", "value": 312.4, "color": "#hex optional", "caption": "optional"}]}  (rankings and comparisons)
        - {"type": "donut_chart", "title": "...", "format": "money", "items": [...same as bar_chart, at most 8]}  (shares of a whole)
        - {"type": "line_chart", "title": "...", "format": "money", "labels": ["Jul", "Aug"], "series": [{"name": "Spending", "values": [1200, 980]}]}  (trends over periods)
        - {"type": "table", "title": "...", "columns": [{"key": "merchant", "label": "Merchant", "format": "text"}, {"key": "spent", "label": "Spent", "format": "money"}], "rows": [{"merchant": "Albert Heijn", "spent": 120.5}]}
        - {"type": "transactions", "title": "...", "ids": [123, 456]}  (show real transactions by id from tool results; the app fills in the details)
        - {"type": "callout", "tone": "insight|tip|warning|positive", "title": "...", "text": "Markdown"}  (one notable finding or recommendation)
        - {"type": "suggestions", "items": ["Short follow-up question the user might ask next"]}

        Formats: "money" (euros, value is a plain number), "percent" (value 12.5 means 12.5%), "number", "text".

        Guidelines:
        - Lead with the direct answer in one or two sentences, then show the evidence with the most fitting visual. Pick visuals that make the answer clearer; do not add charts for their own sake.
        - When listing specific transactions, use a "transactions" block with their ids rather than a table.
        - Use the category colours from tool results in charts.
        - Do not repeat the same numbers in text and in a visual.
        - Always end with a "suggestions" block of 2 or 3 useful follow-ups.
        PROMPT;
    }
}
