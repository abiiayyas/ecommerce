<?php

return [
    'default' => env('PAYMENT_GATEWAY', 'midtrans'),

    'drivers' => [
        'paywuz' => [
            'api_key' => env('PAYWUZ_API_KEY'),
            'base_url' => env('PAYWUZ_BASE_URL', 'https://api.paywuz.id/v1'),
            'connect_timeout' => env('PAYWUZ_CONNECT_TIMEOUT', 3),
            'timeout' => env('PAYWUZ_TIMEOUT', 10),
            'expiry_minutes' => ($expiryMinutes = (int) env('PAYWUZ_EXPIRY_MINUTES', 0)) > 0 ? $expiryMinutes : null,
            'redirect_url' => env('PAYWUZ_REDIRECT_URL'),
            'fee_by_merchant' => env('PAYWUZ_FEE_BY_MERCHANT'),
            'payment_reminder_enabled' => env('PAYWUZ_PAYMENT_REMINDER_ENABLED'),
        ],
    ],
];
