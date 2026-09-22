<?php

return [

    'default_provider' => env('MARKETING_EVENT_PROVIDER', 'meta'),

    'providers' => [
        'meta' => [
            'pixel_id' => env('META_PIXEL_ID'),
            'access_token' => env('META_CONVERSIONS_API_TOKEN'),
            'api_version' => env('META_API_VERSION', 'v24.0'),
            'test_event_code' => env('META_TEST_EVENT_CODE'),
            'connect_timeout' => (int) env('META_CONNECT_TIMEOUT', 3),
            'timeout' => (int) env('META_TIMEOUT', 10),
        ],
    ],

];
