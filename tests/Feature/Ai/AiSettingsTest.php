<?php

namespace Tests\Feature\Ai;

use App\Livewire\Settings\AiAssistant;
use App\Models\Setting;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\AiSettings;
use App\Services\Ai\Providers\GeminiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Livewire\Livewire;
use Tests\TestCase;

class AiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_are_off_with_the_configured_provider_and_model(): void
    {
        $settings = app(AiSettings::class);

        $this->assertFalse($settings->isConfigured());
        $this->assertSame('gemini', $settings->provider());
        $this->assertSame('gemini-2.5-flash', $settings->model());
    }

    public function test_the_api_key_is_stored_encrypted(): void
    {
        app(AiSettings::class)->save(true, 'gemini', 'gemini-2.5-pro', 'AIza-secret');

        $stored = Setting::getValue('ai.gemini.api_key');

        $this->assertNotSame('AIza-secret', $stored);
        $this->assertStringNotContainsString('AIza-secret', $stored);
        $this->assertSame('AIza-secret', app(AiSettings::class)->apiKey());
        $this->assertTrue(app(AiSettings::class)->isConfigured());
    }

    public function test_saving_without_a_key_keeps_the_stored_one(): void
    {
        $settings = app(AiSettings::class);
        $settings->save(true, 'gemini', 'gemini-2.5-pro', 'AIza-secret');
        $settings->save(true, 'gemini', 'gemini-2.5-flash');

        $this->assertSame('AIza-secret', $settings->apiKey());
        $this->assertSame('gemini-2.5-flash', $settings->model());
    }

    public function test_an_unknown_stored_provider_falls_back_to_the_default(): void
    {
        Setting::putValue('ai.provider', 'skynet');

        $this->assertSame('gemini', app(AiSettings::class)->provider());
    }

    public function test_the_manager_builds_the_selected_provider(): void
    {
        app(AiSettings::class)->save(true, 'gemini', 'gemini-2.5-pro', 'AIza-secret');

        $this->assertInstanceOf(GeminiProvider::class, app(AiProviderManager::class)->current());
    }

    public function test_settings_form_saves_the_configuration(): void
    {
        Livewire::test(AiAssistant::class)
            ->assertSet('enabled', false)
            ->assertSet('model', 'gemini-2.5-flash')
            ->set('enabled', true)
            ->set('model', 'gemini-2.5-pro')
            ->set('apiKey', 'AIza-secret')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('apiKey', '')
            ->assertDispatched('toast');

        $settings = app(AiSettings::class);
        $this->assertTrue($settings->isConfigured());
        $this->assertSame('gemini-2.5-pro', $settings->model());
        $this->assertSame('AIza-secret', $settings->apiKey());
    }

    public function test_the_fallback_model_defaults_to_the_provider_setting_and_can_be_turned_off(): void
    {
        $settings = app(AiSettings::class);
        $this->assertSame('gemini-2.5-flash-lite', $settings->fallbackModel());

        Livewire::test(AiAssistant::class)
            ->assertSet('fallbackModel', 'gemini-2.5-flash-lite')
            ->set('fallbackModel', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($settings->fallbackModel());

        $settings->save(false, 'gemini', 'gemini-2.5-flash', fallbackModel: 'gemini-2.0-flash');
        $this->assertSame('gemini-2.0-flash', $settings->fallbackModel());
    }

    public function test_the_fallback_model_must_differ_from_the_main_model(): void
    {
        Livewire::test(AiAssistant::class)
            ->set('fallbackModel', 'gemini-2.5-flash')
            ->call('save')
            ->assertHasErrors('fallbackModel');
    }

    public function test_the_manager_passes_the_fallback_model_to_the_provider(): void
    {
        Sleep::fake();
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent' => Http::response(['error' => ['message' => 'high demand']], 503),
            'generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Lite']]]]]]),
        ]);
        app(AiSettings::class)->save(true, 'gemini', 'gemini-2.5-flash', 'AIza-secret');

        $reply = app(AiProviderManager::class)->current()->generate('System', [['role' => 'user', 'content' => 'Hi']]);

        $this->assertSame('Lite', $reply->text);
    }

    public function test_turning_on_without_a_key_is_rejected(): void
    {
        Livewire::test(AiAssistant::class)
            ->set('enabled', true)
            ->call('save')
            ->assertHasErrors('apiKey');

        $this->assertFalse(app(AiSettings::class)->isEnabled());
    }

    public function test_settings_form_validates_provider_and_model(): void
    {
        Livewire::test(AiAssistant::class)
            ->set('provider', 'skynet')
            ->set('model', 'gemini 2.5; drop table')
            ->call('save')
            ->assertHasErrors(['provider', 'model']);
    }

    public function test_the_api_key_can_be_removed(): void
    {
        app(AiSettings::class)->save(true, 'gemini', 'gemini-2.5-flash', 'AIza-secret');

        Livewire::test(AiAssistant::class)
            ->assertSee('saved. Type a new key to replace it.')
            ->call('forgetApiKey')
            ->assertSet('enabled', false);

        $this->assertFalse(app(AiSettings::class)->hasApiKey());
        $this->assertFalse(app(AiSettings::class)->isEnabled());
    }

    public function test_connection_test_reports_success(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'OK']]]]]])]);

        Livewire::test(AiAssistant::class)
            ->set('apiKey', 'AIza-secret')
            ->call('testConnection')
            ->assertSet('testResult.ok', true)
            ->assertSee('Connected to gemini-2.5-flash');

        $this->assertFalse(app(AiSettings::class)->hasApiKey());
    }

    public function test_connection_test_reports_failure(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'API key not valid.']], 400)]);

        Livewire::test(AiAssistant::class)
            ->set('apiKey', 'wrong')
            ->call('testConnection')
            ->assertSet('testResult.ok', false)
            ->assertSee('API key not valid.');
    }

    public function test_available_models_can_be_loaded(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['models' => [
            ['name' => 'models/gemini-2.5-flash', 'supportedGenerationMethods' => ['generateContent']],
        ]])]);

        Livewire::test(AiAssistant::class)
            ->set('apiKey', 'AIza-secret')
            ->call('loadModels')
            ->assertSet('availableModels', ['gemini-2.5-flash']);
    }

    public function test_the_settings_page_shows_the_assistant_section(): void
    {
        $this->withSession(['authenticated' => true])
            ->get(route('settings'))
            ->assertOk()
            ->assertSeeLivewire(AiAssistant::class)
            ->assertSee('AI assistant');
    }
}
