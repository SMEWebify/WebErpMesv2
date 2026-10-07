<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provider par défaut
    |--------------------------------------------------------------------------
    | 'claude' | 'python_ml'
    | (l'assistant ERP suit, lui, le provider choisi dans /admin/integrations/ai)
    */
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'claude'),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [

        'claude' => [
            'api_key'       => env('ANTHROPIC_API_KEY'),
            'api_url'       => 'https://api.anthropic.com/v1/messages',
            'api_version'   => '2023-06-01',
            // Modèles disponibles :
            //   claude-haiku-4-5-20251001   → rapide, économique (suggestions inline)
            //   claude-sonnet-4-6           → analyses complexes
            'default_model' => env('AI_CLAUDE_MODEL', 'claude-haiku-4-5-20251001'),
            'max_tokens'    => (int) env('AI_CLAUDE_MAX_TOKENS', 1024),
            'timeout'       => (int) env('AI_CLAUDE_TIMEOUT', 30),
        ],

        // OVHcloud AI Endpoints — API compatible OpenAI (/chat/completions),
        // hébergée en France. Sans clé, l'accès anonyme fonctionne mais est
        // fortement limité en débit. Seuls les modèles marqués « Function
        // Calling » au catalogue peuvent piloter l'assistant ERP.
        'ovh' => [
            'api_key'       => env('OVH_AI_ENDPOINTS_ACCESS_TOKEN'),
            'base_url'      => env('OVH_AI_ENDPOINTS_URL', 'https://oai.endpoints.kepler.ai.cloud.ovh.net/v1'),
            'default_model' => env('AI_OVH_MODEL', 'Mistral-Small-3.2-24B-Instruct-2506'),
            'max_tokens'    => (int) env('AI_OVH_MAX_TOKENS', 2048),
            'timeout'       => (int) env('AI_OVH_TIMEOUT', 60),
        ],

        'python_ml' => [
            // Mode 'http'    → appel FastAPI (cible à terme)
            // Mode 'command' → exécution script local (mode actuel pré-commandes)
            'mode'       => env('AI_PYTHON_MODE', 'command'),
            'base_url'   => env('AI_PYTHON_URL', 'http://localhost:8001'),
            'timeout'    => (int) env('AI_PYTHON_TIMEOUT', 60),
            'executable' => env('PRE_ORDERS_PYTHON_EXECUTABLE', 'python'),
            'script'     => env('PRE_ORDERS_PYTHON_SCRIPT'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Transcription audio (mémo vocal de visite)
    |--------------------------------------------------------------------------
    | Whisper sur OVHcloud AI Endpoints, avec la clé OVH déjà utilisée par
    | l'assistant. Sans clé, l'écran de visite retombe sur la dictée du
    | navigateur. L'audio n'est jamais conservé : il transite par le fichier
    | temporaire de la requête et disparaît avec elle.
    | url vide → {base_url OVH}/audio/transcriptions
    */
    'transcription' => [
        'enabled' => (bool) env('AI_TRANSCRIPTION_ENABLED', true),
        'url'     => env('AI_TRANSCRIPTION_URL'),
        'model'   => env('AI_TRANSCRIPTION_MODEL', 'whisper-large-v3'),
        'timeout' => (int) env('AI_TRANSCRIPTION_TIMEOUT', 180),
        // Ko — Whisper refuse au-delà de 25 Mo
        'max_size' => (int) env('AI_TRANSCRIPTION_MAX_SIZE', 25600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue pour les appels asynchrones
    |--------------------------------------------------------------------------
    */
    'queue' => env('AI_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    | true → loggue chaque requête/réponse dans le channel 'ai'
    */
    'logging' => (bool) env('AI_LOGGING', false),

];
