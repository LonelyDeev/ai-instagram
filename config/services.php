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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'google' => [
        'client_id' => '670042885445-cmq6tngijf26gjbmjcq73m5r5s94q0up.apps.googleusercontent.com', 
        'client_secret' => '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',
        'redirect' => 'https://aiwriter.paponapps.co.in/checklogin/google/callback-google'
    ],
    'facebook' => [
        'client_id' => '5824705190970928',
        'client_secret' => '62e852fbb48ce4c9c5e7d605f8a436fa',
        'redirect' => 'https://aiwriter.paponapps.co.in/checklogin/facebook/callback-facebook',
    ],
];
