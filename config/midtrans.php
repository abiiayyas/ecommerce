<?php

return [
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'connect_timeout' => env('MIDTRANS_CONNECT_TIMEOUT', 5),
    'timeout' => env('MIDTRANS_TIMEOUT', 15),
    'retries' => env('MIDTRANS_RETRIES', 3),
    'timezone' => env('MIDTRANS_TIMEZONE', 'Asia/Jakarta'),
];
