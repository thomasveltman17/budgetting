<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\BlockParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockParserTest extends TestCase
{
    use BuildsFinanceFixtures;
    use RefreshDatabase;

    private BlockParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new BlockParser;
    }

    public function test_it_reads_a_blocks_object(): void
    {
        $blocks = $this->parser->parse('{"blocks": [{"type": "text", "text": "Hello **there**"}]}');

        $this->assertSame([['type' => 'text', 'text' => 'Hello **there**']], $blocks);
    }

    public function test_it_tolerates_code_fences_and_surrounding_text(): void
    {
        $blocks = $this->parser->parse("Here you go:\n```json\n{\"blocks\": [{\"type\": \"suggestions\", \"items\": [\"Next?\"]}]}\n```");

        $this->assertSame([['type' => 'suggestions', 'items' => ['Next?']]], $blocks);
    }

    public function test_plain_text_becomes_a_text_block(): void
    {
        $this->assertSame([['type' => 'text', 'text' => 'You spent a lot on coffee.']], $this->parser->parse('You spent a lot on coffee.'));
    }

    public function test_empty_or_useless_answers_get_a_fallback_message(): void
    {
        $this->assertSame('text', $this->parser->parse('')[0]['type']);
        $this->assertStringContainsString('could not come up with an answer', $this->parser->parse('{"blocks": [{"type": "hologram"}]}')[0]['text']);
    }

    public function test_unknown_blocks_and_invalid_items_are_dropped(): void
    {
        $blocks = $this->parser->parse(json_encode(['blocks' => [
            ['type' => 'script', 'text' => '<script>alert(1)</script>'],
            ['type' => 'bar_chart', 'title' => 'Top', 'format' => 'money', 'items' => [
                ['label' => 'Rent', 'value' => 800, 'color' => '#ef4444'],
                ['label' => 'Bad', 'value' => 'lots'],
                ['label' => 'Groceries', 'value' => '45.5', 'color' => 'red; background: url(x)'],
            ]],
        ]]));

        $this->assertCount(1, $blocks);
        $this->assertSame([
            ['label' => 'Rent', 'value' => 800.0, 'color' => '#ef4444', 'caption' => null],
            ['label' => 'Groceries', 'value' => 45.5, 'color' => null, 'caption' => null],
        ], $blocks[0]['items']);
    }

    public function test_stats_callouts_tables_and_line_charts_are_normalised(): void
    {
        $blocks = $this->parser->parse(json_encode(['blocks' => [
            ['type' => 'stats', 'items' => [['label' => 'Spent', 'value' => 930.5, 'format' => 'money', 'tone' => 'loud']]],
            ['type' => 'callout', 'tone' => 'shouting', 'text' => 'Cut coffee'],
            ['type' => 'table', 'columns' => [['key' => 'merchant', 'label' => 'Merchant'], ['key' => 'spent', 'format' => 'money']], 'rows' => [['merchant' => 'AH', 'spent' => '12.5', 'extra' => 'ignored']]],
            ['type' => 'line_chart', 'labels' => ['Jul', 'Aug', 'Sep'], 'series' => [['name' => 'Spending', 'values' => [900, 'x']]]],
        ]]));

        $this->assertSame('neutral', $blocks[0]['items'][0]['tone']);
        $this->assertSame('insight', $blocks[1]['tone']);
        $this->assertSame([['merchant' => 'AH', 'spent' => 12.5]], $blocks[2]['rows']);
        $this->assertSame('spent', $blocks[2]['columns'][1]['label']);
        $this->assertSame([900.0, 0.0, 0.0], $blocks[3]['series'][0]['values']);
    }

    public function test_transaction_blocks_are_filled_from_the_database_and_skip_unknown_ids(): void
    {
        $this->buildFinanceFixtures();

        $blocks = $this->parser->parse(json_encode(['blocks' => [
            ['type' => 'transactions', 'title' => 'Biggest', 'ids' => [$this->fixtures['rent']->id, 99999, $this->fixtures['groceries']->id]],
            ['type' => 'transactions', 'ids' => [99999]],
        ]]));

        $this->assertCount(1, $blocks);
        $this->assertSame(['Rent', 'ALBERT HEIJN 1234'], array_column($blocks[0]['transactions'], 'description'));
        $this->assertSame(-800.0, $blocks[0]['transactions'][0]['amount']);
        $this->assertSame('Fixed Costs', $blocks[0]['transactions'][0]['category']);
        $this->assertSame('Weekly shop', $blocks[0]['transactions'][1]['notes']);
    }
}
