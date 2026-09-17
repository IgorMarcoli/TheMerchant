<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| API & Webhooks
|--------------------------------------------------------------------------
| Endpoints para comunicação assíncrona do gateway de pagamentos
*/

Route::post('/webhooks/payment', [WebhookController::class, 'handlePaymentWebhook'])
    ->name('api.webhooks.payment');
