<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\AiException;
use App\Services\Ai\Providers\GeminiProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class GeminiProviderTest extends TestCase
{
    private const ENDPOINT = 'generativelanguage.googleapis.com/v1beta/models/gemini-test:generateContent';

    private function provider(): GeminiProvider
    {
        return new GeminiProvider('secret-key', 'gemini-test', ['temperature' => 0.2]);
    }

    public function test_it_sends_the_prompt_and_returns_text(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'candidates' => [['content' => ['role' => 'model', 'parts' => [
                ['text' => 'Thinking about it…', 'thought' => true],
                ['text' => '{"blocks": []}'],
            ]]]],
        ])]);

        $reply = $this->provider()->generate('Be helpful.', [['role' => 'user', 'content' => 'Hi']]);

        $this->assertSame('{"blocks": []}', $reply->text);
        $this->assertFalse($reply->wantsTools());

        Http::assertSent(fn (Request $request) => $request->hasHeader('x-goog-api-key', 'secret-key')
            && $request['systemInstruction']['parts'][0]['text'] === 'Be helpful.'
            && $request['contents'] === [['role' => 'user', 'parts' => [['text' => 'Hi']]]]
            && $request['generationConfig']['temperature'] === 0.2
            && ! isset($request['tools']));
    }

    public function test_it_declares_tools_and_parses_function_calls_with_their_signature(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'candidates' => [['content' => ['role' => 'model', 'parts' => [
                ['functionCall' => ['name' => 'top_transactions', 'args' => ['limit' => 3]], 'thoughtSignature' => 'sig-1'],
                ['functionCall' => ['name' => 'list_periods']],
            ]]]],
        ])]);

        $reply = $this->provider()->generate('System', [['role' => 'user', 'content' => 'Biggest spends?']], [
            ['name' => 'top_transactions', 'description' => 'Largest expenses', 'parameters' => ['type' => 'object', 'properties' => ['limit' => ['type' => 'integer']]]],
            ['name' => 'list_periods', 'description' => 'Periods'],
        ]);

        $this->assertNull($reply->text);
        $this->assertCount(2, $reply->toolCalls);
        $this->assertSame('top_transactions', $reply->toolCalls[0]['name']);
        $this->assertSame(['limit' => 3], $reply->toolCalls[0]['args']);
        $this->assertSame(['thoughtSignature' => 'sig-1'], $reply->toolCalls[0]['extra']);
        $this->assertSame([], $reply->toolCalls[1]['args']);

        Http::assertSent(fn (Request $request) => $request['tools'][0]['functionDeclarations'][0]['name'] === 'top_transactions'
            && ! array_key_exists('parameters', $request['tools'][0]['functionDeclarations'][1]));
    }

    public function test_tool_turns_are_echoed_back_in_gemini_format(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Done']]]]]])]);

        $this->provider()->generate('System', [
            ['role' => 'user', 'content' => 'Biggest spends?'],
            ['role' => 'assistant', 'content' => null, 'tool_calls' => [
                ['id' => 'call_abc', 'name' => 'list_periods', 'args' => [], 'extra' => ['thoughtSignature' => 'sig-1']],
            ]],
            ['role' => 'tool', 'results' => [
                ['id' => 'call_abc', 'name' => 'list_periods', 'output' => ['periods' => []]],
            ]],
        ]);

        Http::assertSent(function (Request $request) {
            $body = json_decode($request->body(), true);
            [, $model, $tool] = $body['contents'];

            return $model['role'] === 'model'
                && $model['parts'][0]['functionCall'] === ['name' => 'list_periods', 'args' => []]
                && str_contains($request->body(), '"args":{}')
                && $model['parts'][0]['thoughtSignature'] === 'sig-1'
                && $tool['role'] === 'user'
                && $tool['parts'][0]['functionResponse'] === ['id' => 'call_abc', 'name' => 'list_periods', 'response' => ['result' => ['periods' => []]]];
        });
    }

    public function test_api_errors_become_readable_exceptions(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['error' => ['message' => 'API key not valid.']], 400)]);

        $this->expectException(AiException::class);
        $this->expectExceptionMessage('Gemini rejected the request: API key not valid.');

        $this->provider()->generate('System', [['role' => 'user', 'content' => 'Hi']]);
    }

    public function test_an_unknown_model_points_to_settings(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['error' => ['message' => 'not found']], 404)]);

        $this->expectExceptionMessage('Gemini does not know the model "gemini-test"');

        $this->provider()->generate('System', [['role' => 'user', 'content' => 'Hi']]);
    }

    public function test_an_empty_answer_reports_the_finish_reason(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['candidates' => [['finishReason' => 'SAFETY']]])]);

        $this->expectExceptionMessage('reason: SAFETY');

        $this->provider()->generate('System', [['role' => 'user', 'content' => 'Hi']]);
    }

    public function test_an_overloaded_model_is_retried_until_it_answers(): void
    {
        Sleep::fake();
        Http::fake([self::ENDPOINT => Http::sequence()
            ->push(['error' => ['message' => 'This model is currently experiencing high demand.']], 503)
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Answer']]]]]]),
        ]);

        $reply = $this->provider()->generate('System', [['role' => 'user', 'content' => 'Hi']]);

        $this->assertSame('Answer', $reply->text);
        Http::assertSentCount(2);
        Sleep::assertSleptTimes(1);
    }

    public function test_it_gives_up_after_retrying_with_a_temporary_error(): void
    {
        Sleep::fake();
        Http::fake([self::ENDPOINT => Http::response(['error' => ['message' => 'high demand']], 503)]);

        try {
            $this->provider()->generate('System', [['role' => 'user', 'content' => 'Hi']]);
            $this->fail('Expected an AiException.');
        } catch (AiException $exception) {
            $this->assertTrue($exception->isTemporary);
            $this->assertStringContainsString("Gemini's gemini-test is overloaded right now", $exception->getMessage());
        }

        Http::assertSentCount(3);
    }

    public function test_client_errors_are_not_retried(): void
    {
        Sleep::fake();
        Http::fake([self::ENDPOINT => Http::response(['error' => ['message' => 'API key not valid.']], 400)]);

        try {
            $this->provider()->generate('System', [['role' => 'user', 'content' => 'Hi']]);
        } catch (AiException $exception) {
            $this->assertFalse($exception->isTemporary);
        }

        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
    }

    public function test_it_switches_to_the_fallback_model_and_keeps_using_it(): void
    {
        Sleep::fake();
        Http::fake([
            self::ENDPOINT => Http::response(['error' => ['message' => 'high demand']], 503),
            'generativelanguage.googleapis.com/v1beta/models/gemini-lite:generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'From the fallback']]]]]]),
        ]);

        $provider = new GeminiProvider('secret-key', 'gemini-test', ['fallback_model' => 'gemini-lite']);

        $this->assertSame('From the fallback', $provider->generate('System', [['role' => 'user', 'content' => 'Hi']])->text);
        $this->assertSame('From the fallback', $provider->generate('System', [['role' => 'user', 'content' => 'Again']])->text);

        Http::assertSentCount(5);
    }

    public function test_it_explains_when_the_fallback_is_overloaded_too(): void
    {
        Sleep::fake();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'high demand']], 503)]);

        $this->expectExceptionMessage('both gemini-test and gemini-lite are busy');

        (new GeminiProvider('secret-key', 'gemini-test', ['fallback_model' => 'gemini-lite']))
            ->generate('System', [['role' => 'user', 'content' => 'Hi']]);
    }

    public function test_a_broken_fallback_keeps_the_overload_message(): void
    {
        Sleep::fake();
        Http::fake([
            self::ENDPOINT => Http::response(['error' => ['message' => 'high demand']], 503),
            'generativelanguage.googleapis.com/v1beta/models/gemini-gone:generateContent' => Http::response(['error' => ['message' => 'not found']], 404),
        ]);

        $this->expectExceptionMessage("Gemini's gemini-test is overloaded right now. This is usually temporary, so try again in a minute, or set a fallback model in Settings. The fallback model could not be used either: Gemini does not know the model \"gemini-gone\"");

        (new GeminiProvider('secret-key', 'gemini-test', ['fallback_model' => 'gemini-gone']))
            ->generate('System', [['role' => 'user', 'content' => 'Hi']]);
    }

    public function test_it_does_not_fall_back_on_errors_another_model_cannot_fix(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['error' => ['message' => 'API key not valid.']], 400)]);

        $this->expectExceptionMessage('API key not valid.');

        (new GeminiProvider('secret-key', 'gemini-test', ['fallback_model' => 'gemini-lite']))
            ->generate('System', [['role' => 'user', 'content' => 'Hi']]);

        Http::assertSentCount(1);
    }

    public function test_it_lists_chat_models(): void
    {
        Http::fake(['generativelanguage.googleapis.com/v1beta/models*' => Http::response(['models' => [
            ['name' => 'models/gemini-2.5-pro', 'supportedGenerationMethods' => ['generateContent']],
            ['name' => 'models/gemini-2.5-flash', 'supportedGenerationMethods' => ['generateContent', 'countTokens']],
            ['name' => 'models/text-embedding-004', 'supportedGenerationMethods' => ['embedContent']],
        ]])]);

        $this->assertSame(['gemini-2.5-flash', 'gemini-2.5-pro'], $this->provider()->listModels());
    }

    public function test_listing_models_with_a_bad_key_returns_nothing(): void
    {
        Http::fake(['generativelanguage.googleapis.com/v1beta/models*' => Http::response(['error' => ['message' => 'bad key']], 400)]);

        $this->assertSame([], $this->provider()->listModels());
    }
}
