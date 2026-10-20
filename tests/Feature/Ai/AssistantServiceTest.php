<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\AiException;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\AiReply;
use App\Services\Ai\AssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantServiceTest extends TestCase
{
    use BuildsFinanceFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildFinanceFixtures();
    }

    private function fakeProvider(AiReply ...$replies): FakeAiProvider
    {
        $provider = new FakeAiProvider($replies);
        $this->mock(AiProviderManager::class)->shouldReceive('current')->andReturn($provider);

        return $provider;
    }

    public function test_it_runs_requested_tools_and_returns_blocks_from_the_final_answer(): void
    {
        $provider = $this->fakeProvider(
            new AiReply(null, [['id' => 'call_1', 'name' => 'top_transactions', 'args' => ['limit' => 2], 'extra' => []]]),
            new AiReply(json_encode(['blocks' => [
                ['type' => 'text', 'text' => 'Rent was your biggest expense.'],
                ['type' => 'transactions', 'ids' => [$this->fixtures['rent']->id]],
            ]])),
        );

        $result = app(AssistantService::class)->ask('What were my biggest expenses?');

        $this->assertSame(['text', 'transactions'], array_column($result['blocks'], 'type'));
        $this->assertCount(2, $provider->requests);

        $toolTurn = $provider->requests[1]['messages'][2];
        $this->assertSame('tool', $toolTurn['role']);
        $this->assertSame('top_transactions', $toolTurn['results'][0]['name']);
        $this->assertSame(['Rent', 'Dinner with friends'], array_column($toolTurn['results'][0]['output']['transactions'], 'description'));
    }

    public function test_the_system_prompt_describes_the_period_categories_and_answer_format(): void
    {
        $provider = $this->fakeProvider(new AiReply('{"blocks": [{"type": "text", "text": "Hi"}]}'));

        app(AssistantService::class)->ask('Hi');

        $system = $provider->requests[0]['system'];
        $this->assertStringContainsString('Thursday 20 August 2026', $system);
        $this->assertStringContainsString('15 August – 14 September 2026', $system);
        $this->assertStringContainsString('- Investments (type: investment)', $system);
        $this->assertStringContainsString('American Express', $system);
        $this->assertStringContainsString('{"blocks": [ ... ]}', $system);
        $this->assertNotEmpty($provider->requests[0]['tools']);
    }

    public function test_history_keeps_only_questions_and_answers(): void
    {
        $provider = $this->fakeProvider(
            new AiReply(null, [['id' => 'call_1', 'name' => 'list_periods', 'args' => [], 'extra' => []]]),
            new AiReply('{"blocks": [{"type": "text", "text": "Second answer"}]}'),
        );

        $earlier = [['role' => 'user', 'content' => 'First question'], ['role' => 'assistant', 'content' => 'First answer']];
        $result = app(AssistantService::class)->ask('Second question', $earlier);

        $this->assertSame('First question', $provider->requests[0]['messages'][0]['content']);
        $this->assertSame(['user', 'assistant', 'user', 'assistant'], array_column($result['history'], 'role'));
        $this->assertSame('{"blocks": [{"type": "text", "text": "Second answer"}]}', $result['history'][3]['content']);
    }

    public function test_history_is_capped(): void
    {
        config(['ai.max_history_turns' => 2]);
        $this->fakeProvider(new AiReply('Fine'));

        $earlier = collect(range(1, 6))->flatMap(fn ($i) => [['role' => 'user', 'content' => "Q{$i}"], ['role' => 'assistant', 'content' => "A{$i}"]])->all();
        $result = app(AssistantService::class)->ask('Q7', $earlier);

        $this->assertSame(['Q6', 'A6', 'Q7', 'Fine'], array_column($result['history'], 'content'));
    }

    public function test_the_model_must_answer_after_the_last_tool_round(): void
    {
        config(['ai.max_tool_rounds' => 2]);
        $call = new AiReply(null, [['id' => 'call_1', 'name' => 'list_periods', 'args' => [], 'extra' => []]]);
        $provider = $this->fakeProvider($call, $call, new AiReply('Out of lookups, here is what I know.'));

        $result = app(AssistantService::class)->ask('Loop forever');

        $this->assertCount(3, $provider->requests);
        $this->assertSame([], $provider->requests[2]['tools']);
        $this->assertSame('Out of lookups, here is what I know.', $result['blocks'][0]['text']);
    }

    public function test_tool_errors_are_passed_back_to_the_model(): void
    {
        $provider = $this->fakeProvider(
            new AiReply(null, [['id' => 'call_1', 'name' => 'top_transactions', 'args' => ['category' => 'Holidays'], 'extra' => []]]),
            new AiReply('That category does not exist.'),
        );

        app(AssistantService::class)->ask('Holiday spending?');

        $this->assertStringContainsString('Unknown category', $provider->requests[1]['messages'][2]['results'][0]['output']['error']);
    }

    public function test_it_refuses_when_the_assistant_is_not_configured(): void
    {
        $this->expectException(AiException::class);
        $this->expectExceptionMessage('turned off');

        app(AssistantService::class)->ask('Hi');
    }
}
