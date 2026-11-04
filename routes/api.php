<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API & Webhooks
|--------------------------------------------------------------------------
| Endpoints para comunicação assíncrona do gateway de pagamentos e dados
*/

Route::post('/webhooks/payment', [WebhookController::class, 'handlePaymentWebhook'])
    ->middleware('throttle:120,1')
    ->name('api.webhooks.payment');
