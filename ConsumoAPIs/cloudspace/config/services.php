<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe (pasarela de pagos)
    |--------------------------------------------------------------------------
    |
    | key: llave publicable (pk_...), la única que llega al navegador.
    | secret: llave secreta (sk_...), solo la usa App\Services\StripeService.
    | webhook_secret: secreto de firma (whsec_...) para verificar los eventos.
    |
    */

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SendGrid (correo transaccional por API REST v3)
    |--------------------------------------------------------------------------
    |
    | El remitente debe estar verificado en SendGrid (Sender Identity).
    | sandbox: SendGrid valida la petición, pero no entrega el correo.
    |
    */

    'sendgrid' => [
        'key' => env('SENDGRID_API_KEY'),
        'from' => [
            'address' => env('SENDGRID_FROM_ADDRESS'),
            'name' => env('SENDGRID_FROM_NAME', env('APP_NAME')),
        ],
        'sandbox' => (bool) env('SENDGRID_SANDBOX', false),
    ],

];
