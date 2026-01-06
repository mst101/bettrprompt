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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'webhook_signing_key' => env('MAILGUN_WEBHOOK_SIGNING_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'n8n' => [
        'base_url' => env('N8N_BASE_URL'),
        'url' => env('N8N_INTERNAL_URL', 'http://n8n:5678'),
        'username' => env('N8N_BASIC_AUTH_USER'),
        'password' => env('N8N_BASIC_AUTH_PASSWORD'),
        'api_key' => env('N8N_API_KEY'),
        'api_key_live' => env('N8N_API_KEY_LIVE'),
        'webhook_secret' => env('N8N_WEBHOOK_SECRET'),
        'workflow_ids' => [
            0 => env('N8N_WORKFLOW_0_ID', 'x2JKq1wrjnpA76R9'),
            1 => env('N8N_WORKFLOW_1_ID', 'MGuVYvZDLTa3D09d'),
            2 => env('N8N_WORKFLOW_2_ID', 'kbuj1cvHJdjqQjQT'),
        ],
        'workflow_ids_live' => [
            0 => env('N8N_WORKFLOW_0_ID_LIVE'),
            1 => env('N8N_WORKFLOW_1_ID_LIVE'),
            2 => env('N8N_WORKFLOW_2_ID_LIVE'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'testing' => [
        'mock_scenario' => env('TEST_MOCK_SCENARIO'),
    ],
];
