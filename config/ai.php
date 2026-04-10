<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provider par défaut
    |--------------------------------------------------------------------------
    | 'claude' | 'python_ml'
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
