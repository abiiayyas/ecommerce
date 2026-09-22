<?php

return [
    'default' => env('SHIPPING_PROVIDER', 'biteship'),
    'fallback' => env('SHIPPING_FALLBACK_PROVIDER'),
];
