<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Africa's Talking Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Africa's Talking SMS and Voice services.
    | Make sure to add your credentials to the .env file.
    |
    */

    'username' => env('AT_USERNAME'),
    'api_key' => env('AT_API_KEY'),
    'environment' => env('AT_ENVIRONMENT', 'sandbox'), // 'sandbox' or 'production'

    // Never honoured in production — see the SMS services, which force
    // verification on when app.env is production.
    'disable_ssl_verification' => env('AT_DISABLE_SSL_VERIFICATION', false),

    // Path to a CA bundle for TLS verification. Fixes local setups whose
    // PHP has no system CA bundle, without turning verification off.
    'ca_bundle' => env('AT_CA_BUNDLE', base_path('certs/cacert.pem')),
    /*
    |--------------------------------------------------------------------------
    | SMS Configuration
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'sender_id' => env('AT_SENDER_ID', 'SHORTCODE'),
        'max_length' => 160, // Standard SMS length
        'max_recipients' => 100, // Max recipients per bulk SMS request
        'delivery_reports' => env('AT_DELIVERY_REPORTS', true),
        'bulk_sms_mode' => env('AT_BULK_SMS_MODE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice Configuration
    |--------------------------------------------------------------------------
    */
    'voice' => [
        'enabled' => env('AT_VOICE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('AT_LOGGING', true),
        'log_requests' => env('AT_LOG_REQUESTS', true),
        'log_responses' => env('AT_LOG_RESPONSES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'max_attempts' => 3,
        'delay_seconds' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */
    'validation' => [
        // Phone number validation patterns for different countries
        'phone_patterns' => [
            'uganda' => '/^(\+256|0)?[0-9]{9}$/',
            'kenya' => '/^(\+254|0)?[0-9]{9}$/',
            'tanzania' => '/^(\+255|0)?[0-9]{9}$/',
            'rwanda' => '/^(\+250|0)?[0-9]{9}$/',
            'default' => '/^(\+)?[0-9]{10,15}$/',
        ],
        'default_country_code' => '+256', // Uganda
    ],
];