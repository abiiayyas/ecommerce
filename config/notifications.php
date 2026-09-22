<?php

return [

    'channels' => [
        'whatsapp' => [
            'default_provider' => env('WHATSAPP_NOTIFICATION_PROVIDER', 'fonnte'),
            'country_code' => env('WHATSAPP_COUNTRY_CODE', '62'),
        ],
    ],

    'providers' => [
        'fonnte' => [
            'token' => env('FONNTE_TOKEN'),
            'base_url' => env('FONNTE_BASE_URL', 'https://api.fonnte.com/send'),
            'connect_timeout' => (int) env('FONNTE_CONNECT_TIMEOUT', 3),
            'timeout' => (int) env('FONNTE_TIMEOUT', 10),
        ],
    ],

];
