<?php

return [
    'api_key' => env('DISCHUB_API_KEY'),
    'api_url' => 'https://dischub.co.zw/api/orders/create/',
    'payment_url' => 'https://dischub.co.zw/api/make/payment/to/',
    'notify_url' => env('DISCHUB_NOTIFY_URL'),
    'recipient' => env('DISCHUB_RECIPIENT_EMAIL'),
];
