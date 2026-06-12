<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS providers like Africa's Talking, Twilio, etc.
    |
    */

    'sms' => [
        'default' => env('SMS_PROVIDER', 'africas_talking'),

        'providers' => [
            'africas_talking' => [
                'api_key' => env('AFRICAS_TALKING_API_KEY'),
                'username' => env('AFRICAS_TALKING_USERNAME'),
                'sender_id' => env('AFRICAS_TALKING_SENDER_ID'),
                'sandbox' => env('AFRICAS_TALKING_SANDBOX', false),
                // Africa's Talking does not sign webhooks; register the
                // callback URL with ?token=<this value> to authenticate it.
                'webhook_token' => env('AT_WEBHOOK_TOKEN'),
            ],

            'twilio' => [
                'account_sid' => env('TWILIO_ACCOUNT_SID'),
                'auth_token' => env('TWILIO_AUTH_TOKEN'),
                'from_number' => env('TWILIO_FROM_NUMBER'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp Business API providers
    |
    */

    'whatsapp' => [
        'default' => env('WHATSAPP_PROVIDER', 'twilio'),

        'providers' => [
            'twilio' => [
                'account_sid' => env('TWILIO_ACCOUNT_SID'),
                'auth_token' => env('TWILIO_AUTH_TOKEN'),
                'from_number' => env('TWILIO_WHATSAPP_FROM'),
            ],

            'meta' => [
                'access_token' => env('META_WHATSAPP_ACCESS_TOKEN'),
                'phone_number_id' => env('META_WHATSAPP_PHONE_NUMBER_ID'),
                'app_id' => env('META_WHATSAPP_APP_ID'),
                'app_secret' => env('META_WHATSAPP_APP_SECRET'),
                'verify_token' => env('META_WHATSAPP_VERIFY_TOKEN'),
                'webhook_secret' => env('META_WHATSAPP_WEBHOOK_SECRET'),
            ],
        ],
    ],

];
