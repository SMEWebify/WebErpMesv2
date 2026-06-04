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

    // Plateforme de Dématérialisation Partenaire (PDP) active pour l'émission
    // des factures électroniques. Les drivers sont enregistrés dans AppServiceProvider.
    'pdp' => [
        'default' => env('PDP_DRIVER', 'qonto'),
    ],

];
