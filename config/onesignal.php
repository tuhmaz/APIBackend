<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable/Disable OneSignal
    |--------------------------------------------------------------------------
    |
    | Toggle OneSignal integration globally. Set ONESIGNAL_ENABLED=false in .env
    | to disable all outgoing push notifications safely.
    |
    */
    'enabled' => env('ONESIGNAL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | One Signal App Id
    |--------------------------------------------------------------------------
    |
    | Your OneSignal App Id
    |
    */
    'app_id' => env('ONESIGNAL_APP_ID'),

    /*
    |--------------------------------------------------------------------------
    | Rest API Key
    |--------------------------------------------------------------------------
    |
    | Your OneSignal REST API Key
    |
    */
    'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Guzzle Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout for Guzzle client
    |
    */
    'guzzle_client_timeout' => env('ONESIGNAL_GUZZLE_CLIENT_TIMEOUT', 0),
];
