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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'enabled' => env('TELEGRAM_NOTIFICATIONS_ENABLED', true),
    ],

    'ciudadania_digital' => [
        'enabled' => env('CIUDADANIA_DIGITAL_ENABLED', false),
        'issuer' => env('CIUDADANIA_DIGITAL_ISSUER', 'https://proveedor.ciudadania.demo.agetic.gob.bo'),
        'client_id' => env('CIUDADANIA_DIGITAL_CLIENT_ID'),
        'client_secret' => env('CIUDADANIA_DIGITAL_CLIENT_SECRET'),
        'redirect_uri' => env('CIUDADANIA_DIGITAL_REDIRECT_URI'),
        'post_logout_redirect_uri' => env('CIUDADANIA_DIGITAL_POST_LOGOUT_REDIRECT_URI'),
        'scope' => env('CIUDADANIA_DIGITAL_SCOPE', 'openid profile email'),
        'prompt' => env('CIUDADANIA_DIGITAL_PROMPT'),
        'timeout' => env('CIUDADANIA_DIGITAL_TIMEOUT', 12),
    ],

];
