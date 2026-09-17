<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\TableDataController;

/*
|--------------------------------------------------------------------------
| API & Webhooks
|--------------------------------------------------------------------------
| Endpoints para comunicação assíncrona do gateway de pagamentos e dados
*/

Route::post('/webhooks/payment', [WebhookController::class, 'handlePaymentWebhook'])
    ->name('api.webhooks.payment');

// Endpoint de consulta rápida de dados das tabelas em JSON
Route::get('/tabela', [TableDataController::class, 'index'])->name('api.tables.index');
Route::get('/tabela/{table}', [TableDataController::class, 'show'])->name('api.tables.show');
