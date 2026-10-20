<?php

use App\Services\Ai\Providers\GeminiProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | AI assistant
    |--------------------------------------------------------------------------
    |
    | The provider, model and API key are chosen in Settings and stored in the
    | database. This file lists the providers the app knows how to talk to.
    | Add a provider by implementing App\Services\Ai\Providers\AiProvider and
    | registering it below; it then appears in the Settings picker.
    |
    */

    'default_provider' => 'gemini',

    'providers' => [
        'gemini' => [
            'label' => 'Google Gemini',
            'driver' => GeminiProvider::class,
            'default_model' => 'gemini-2.5-flash',
            // Used when the main model stays overloaded after retrying.
            'fallback_model' => 'gemini-2.5-flash-lite',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'key_url' => 'https://aistudio.google.com/app/apikey',
        ],
    ],

    'temperature' => 0.4,

    'timeout' => 60,

    // Tool-calling rounds the model may take before it has to answer.
    'max_tool_rounds' => 6,

    // Earlier questions and answers sent along as context.
    'max_history_turns' => 8,

];
