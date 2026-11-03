<?php

/*
|--------------------------------------------------------------------------
| Serviços de terceiros
|--------------------------------------------------------------------------
| Mesclado com a configuração padrão do framework (Resend, SES, etc.).
*/

return [

    'payment_gateway' => env('PAYMENT_GATEWAY', 'abacatepay'),

    'abacatepay' => [
        'base_url' => env('ABACATEPAY_BASE_URL', 'https://api.abacatepay.com/v2'),
        'api_key' => env('ABACATEPAY_API_KEY'),
        // Segredo enviado pelo provedor na query string (?webhookSecret=...) de cada webhook.
        'webhook_secret' => env('ABACATEPAY_WEBHOOK_SECRET'),
        // Chave pública documentada pela AbacatePay para o HMAC do header X-Webhook-Signature.
        'webhook_public_key' => env(
            'ABACATEPAY_WEBHOOK_PUBLIC_KEY',
            't9dXRhHHo3yDEj5pVDYz0frf7q6bMKyMRmxxCPIPp3RCplBfXRxqlC6ZpiWmOqj4L63qEaeUOtrCI8P0VMUgo6iIga2ri9ogaHFs0WIIywSMg0q7RmBfybe1E5XJcfC4IW3alNqym0tXoAKkzvfEjZxV6bE0oG2zJrNNYmUCKZyV0KZ3JS8Votf9EAWWYdiDkMkpbMdPggfh1EqHlVkMiTady6jOR3hyzGEHrIz2Ret0xHKMbiqkr9HS1JhNHDX9'
        ),
        // Habilita a simulação de pagamento (somente com chave de desenvolvimento).
        'dev_mode' => (bool) env('ABACATEPAY_DEV_MODE', false),
        'timeout' => (int) env('ABACATEPAY_TIMEOUT', 10),
    ],

];
