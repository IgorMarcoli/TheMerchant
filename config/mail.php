<?php
/**
 * Mail configuration for TheMerchant application.
 *
 * This file defines the default mailer and the available mailer transports.
 * The Resend driver is provided by the `resend/resend-laravel` package,
 * which is already required in composer.json.
 */

return [
    /*--------------------------------------------------------------
     | Default Mailer
     --------------------------------------------------------------
     |
     | This option controls the default mailer that is used to send any
     | e‑mail messages sent by your application. The value is read from
     | the `MAIL_MAILER` environment variable, which we set to `resend`
     | in the .env file.
     |
     */
    'default' => env('MAIL_MAILER', 'resend'),

    /*--------------------------------------------------------------
     | Mailer Configurations
     --------------------------------------------------------------
     |
     | Here you may configure all of the mailers used by your application
     | plus their respective settings. The `resend` mailer uses the
     | Resend API key defined in the `.env` file.
     |
     */
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION', null),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'auth_mode' => null,
        ],

        // Resend driver – uses the Resend API key.
        'resend' => [
            'transport' => 'resend',
            'api_key' => env('RESEND_API_KEY'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],
    ],

    /*--------------------------------------------------------------
     | Global "From" Address
     --------------------------------------------------------------
     |
     | This address is used globally for all e‑mails sent by the
     | application. It can be overridden per message.
     |
     */
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME')),
    ],
];

