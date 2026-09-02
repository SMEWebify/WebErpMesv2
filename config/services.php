<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],


    'qonto' => [
        'client_id'      => env('QONTO_CLIENT_ID'),
        'client_secret'  => env('QONTO_CLIENT_SECRET'),
        'oauth_base_url' => env('QONTO_OAUTH_BASE_URL', 'https://oauth.qonto.com'),
        'api_base_url'   => env('QONTO_API_BASE_URL', 'https://thirdparty.qonto.com/v2'),
        'webhook_secret' => env('QONTO_WEBHOOK_SECRET', ''),
    ],

    // SUPER PDP — Plateforme Agréée (PA/PDP) immatriculée, API REST + OAuth 2.1.
    //
    // Le mode bac à sable / production est porté par les *identifiants*, pas par
    // l'URL : le même hôte sert les deux, il n'y a donc pas de base_url de test à
    // basculer. Une clé bac à sable ne peut pas atteindre les données de production.
    'superpdp' => [
        'client_id'     => env('SUPERPDP_CLIENT_ID'),
        'client_secret' => env('SUPERPDP_CLIENT_SECRET'),
        'base_url'      => env('SUPERPDP_BASE_URL', 'https://api.superpdp.tech'),

        // Règle de traitement AFNOR appliquée à nos factures sortantes.
        // B2B = France domestique entre assujettis (cas de WEM).
        'processing_rule' => env('SUPERPDP_PROCESSING_RULE', 'B2B'),

        // Pré-validation schematron (POST /validation_reports) avant dépôt : un
        // appel de plus, mais un refus est renvoyé à l'utilisateur tout de suite
        // au lieu de revenir en `api:invalid` de façon asynchrone.
        'pre_validate'  => env('SUPERPDP_PRE_VALIDATE', true),
    ],

    // Plateforme de Dématérialisation Partenaire (PDP) active pour l'émission
    // des factures électroniques. Les drivers sont enregistrés dans AppServiceProvider.
    'pdp' => [
        'default' => env('PDP_DRIVER', 'qonto'),
    ],

    // NestEngine — moteur d'imbrication en forme exacte (payant). L'écran
    // nesting bascule automatiquement dessus quand `enabled` est vrai ; sinon
    // il retombe sur le shelf packing local (rectangle capable).
    'nestengine' => [
        'enabled'     => env('APP_COMMERCIAL', false) && !empty(env('NESTENGINE_URL')),
        'url'         => env('NESTENGINE_URL'),
        'timeout'     => (int) env('NESTENGINE_TIMEOUT', 120),
        'rotations'   => (int) env('NESTENGINE_ROTATIONS', 36),
        'spacing'     => (float) env('NESTENGINE_SPACING', 5),
        'max_sheets'  => (int) env('NESTENGINE_MAX_SHEETS', 50),
        // Dossier de travail partagé (Laravel écrit, NestEngine lit). Relatif
        // à base_path() sauf s'il s'agit d'un chemin absolu.
        'inputs_dir'  => env('NESTENGINE_INPUTS_DIR', 'storage/app/nesting-inputs'),
    ],

];
