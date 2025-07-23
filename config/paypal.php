<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PayPal Configuration
    |--------------------------------------------------------------------------
    |
    | PayPal SDK Configuration für Rechnungszahlungen
    |
    */

    'mode' => env('PAYPAL_MODE', 'sandbox'), // 'sandbox' oder 'live'
    
        'sandbox' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    ],
    
        'live' => [
        'client_id' => env('PAYPAL_LIVE_CLIENT_ID', env('PAYPAL_CLIENT_ID')),
        'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET', env('PAYPAL_CLIENT_SECRET')),
    ],
    
    'payment_action' => 'Sale',
    'currency' => env('PAYPAL_CURRENCY', 'EUR'),
    'locale' => 'de_DE',
    'validate_ssl' => true,
    
    // Webhook Konfiguration
    'webhook' => [
        'id' => env('PAYPAL_WEBHOOK_ID'),
    ],
    
    // URLs
    'return_url' => env('APP_URL') . '/paypal/success',
    'cancel_url' => env('APP_URL') . '/paypal/cancel',
    
    // Notification URL für IPN
    'notify_url' => env('APP_URL') . '/paypal/webhook',
];
