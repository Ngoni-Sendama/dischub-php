<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Your Dischub API key. It is sent in the X-Api-Key request header.
    |
    */

    'api_key' => env('DISCHUB_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Merchant Account
    |--------------------------------------------------------------------------
    |
    | The email address used to create your Dischub merchant account.
    |
    */

    'recipient' => env('DISCHUB_RECIPIENT', env('DISCHUB_RECIPIENT_EMAIL')),

    /*
    |--------------------------------------------------------------------------
    | Redirect Integration
    |--------------------------------------------------------------------------
    |
    | These values are used by the redirect integration only. The callback URL
    | must use https. The redirect URL may use http or https. Mode is either
    | "test" or "live".
    |
    */

    'callback_url' => env('DISCHUB_CALLBACK_URL', env('DISCHUB_NOTIFY_URL')),

    'redirect_url' => env('DISCHUB_REDIRECT_URL'),

    'mode' => env('DISCHUB_MODE', 'test'),

    /*
    |--------------------------------------------------------------------------
    | Endpoints
    |--------------------------------------------------------------------------
    |
    | The API endpoints can be overridden if needed. "api_url" is kept for
    | backwards compatibility with older published configs.
    |
    */

    'api_url' => env('DISCHUB_ORDERS_URL', env('DISCHUB_API_URL', 'https://dischub.co.zw/api/orders/create/')),

    'payment_url' => env('DISCHUB_PAYMENT_URL', 'https://dischub.co.zw/api/make/payment/to/'),

    'status_url' => env('DISCHUB_STATUS_URL', 'https://dischub.co.zw/api/payment/status/3/step/'),

    'zimswitch_widget_url' => env('DISCHUB_ZIMSWITCH_WIDGET_URL', 'https://dischub.co.zw/api/zimswitch/non/redirect/payment/to/'),

];
