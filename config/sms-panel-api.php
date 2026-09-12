<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The root of your irnoti panel API, without the "/v1/panel" suffix —
    | the client appends that for you. Override per environment if you use
    | a staging panel.
    |
    */
    'base_url' => env('IRNOTI_API_BASE_URL', 'https://api.irnoti.com'),

    /*
    |--------------------------------------------------------------------------
    | Access key
    |--------------------------------------------------------------------------
    |
    | The per-account access key minted from /dashboard/api-keys on your
    | irnoti panel (format: irnt_<random>). Treat it like a password — never
    | commit it, only read it from the environment.
    |
    */
    'key' => env('IRNOTI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Request timeout in seconds for every call made by the client.
    |
    */
    'timeout' => (int) env('IRNOTI_API_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Retries
    |--------------------------------------------------------------------------
    |
    | Automatic retry count + delay (ms) for transient network failures.
    | Set 'times' to 1 to disable retrying.
    |
    */
    'retry' => [
        'times' => (int) env('IRNOTI_API_RETRY_TIMES', 1),
        'sleep' => (int) env('IRNOTI_API_RETRY_SLEEP', 200),
    ],

];
