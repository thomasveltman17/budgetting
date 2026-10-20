<?php

namespace Tests\Feature\Ai;

use App\Livewire\Assistant;
use App\Services\Ai\AiException;
use App\Services\Ai\AiSettings;
use App\Services\Ai\AssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssistantPanelTest extends TestCase
{
    use BuildsFinanceFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildFinanceFixtures();
    }

    private function configure(): void
    {
        app(AiSettings::class)->save(true, 'gemini', 'gemini-2.5-flash', 'test-key');
    }

    public function test_it_asks_for_setup_when_not_configured(): void
    {
        Livewire::test(Assistant::class)
            ->assertSee('Set up the assistant')
            ->assertDontSee('Ask about your spending');
    }

    public function test_it_opens_with_insights_and_starter_questions(): void
    {
        $this->configure();

        Livewire::test(Assistant::class)
            ->assertSee('Google Gemini · gemini-2.5-flash')
            ->assertSee('1 expense without a category')
            ->assertSee('Biggest expense: Rent')
            ->assertSee('What were my three biggest expenses this period?');
    }

    public function test_asking_renders_the_answer_blocks_and_keeps_the_conversation_in_the_session(): void
    {
        $this->configure();
        $this->mock(AssistantService::class)
            ->shouldReceive('ask')
            ->once()
            ->with('Biggest spends?', [])
            ->andReturn([
                'blocks' => [
                    ['type' => 'text', 'text' => 'Rent was **largest**.'],
                    ['type' => 'stats', 'items' => [['label' => 'Spent', 'value' => 930.5, 'format' => 'money', 'caption' => null, 'tone' => 'neutral']]],
                    ['type' => 'suggestions', 'items' => ['Compare with last period']],
                ],
                'history' => [['role' => 'user', 'content' => 'Biggest spends?'], ['role' => 'assistant', 'content' => '{}']],
            ]);

        Livewire::test(Assistant::class)
            ->call('ask', '  Biggest spends?  ')
            ->assertSee('Biggest spends?')
            ->assertSeeHtml('<strong>largest</strong>')
            ->assertSee('€ 930,50')
            ->assertSee('Compare with last period');

        $this->assertCount(2, session('assistant.messages'));
        $this->assertCount(2, session('assistant.history'));

        Livewire::test(Assistant::class)->assertSee('Biggest spends?');
    }

    public function test_provider_errors_are_shown_in_the_conversation(): void
    {
        $this->configure();
        $this->mock(AssistantService::class)
            ->shouldReceive('ask')
            ->andThrow(new AiException('Gemini rate limit or quota reached. Wait a moment and try again.'));

        Livewire::test(Assistant::class)
            ->call('ask', 'Hello')
            ->assertSee('Gemini rate limit or quota reached');
    }

    public function test_an_empty_question_is_rejected(): void
    {
        $this->configure();

        Livewire::test(Assistant::class)
            ->call('ask', '   ')
            ->assertHasErrors('question');
    }

    public function test_a_new_conversation_clears_the_session(): void
    {
        session(['assistant.messages' => [['role' => 'user', 'text' => 'Old question']], 'assistant.history' => [['role' => 'user', 'content' => 'Old question']]]);
        $this->configure();

        Livewire::test(Assistant::class)
            ->assertSee('Old question')
            ->call('newConversation')
            ->assertDontSee('Old question');

        $this->assertNull(session('assistant.messages'));
        $this->assertNull(session('assistant.history'));
    }

    public function test_answer_markdown_is_escaped(): void
    {
        $this->configure();
        $this->mock(AssistantService::class)->shouldReceive('ask')->andReturn([
            'blocks' => [['type' => 'text', 'text' => '<img src=x onerror=alert(1)> and [link](javascript:alert(1))']],
            'history' => [],
        ]);

        Livewire::test(Assistant::class)
            ->call('ask', 'Hi')
            ->assertDontSeeHtml('<img src=x')
            ->assertDontSeeHtml('href="javascript:');
    }

    public function test_every_page_includes_the_assistant(): void
    {
        $this->withSession(['authenticated' => true])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeLivewire(Assistant::class)
            ->assertSee('Ask the assistant');
    }
}
