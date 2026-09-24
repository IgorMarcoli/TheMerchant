<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPaymentWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Recebe webhooks assíncronos do Gateway de Pagamento.
     */
    public function handlePaymentWebhook(Request $request): JsonResponse
    {
        Log::info('Webhook de pagamento recebido:', $request->all());

        $signature = $request->header('x-signature') ?? $request->header('stripe-signature');

        // Despacha para a fila assíncrona (não bloqueia o gateway)
        ProcessPaymentWebhookJob::dispatch($request->all(), $signature);

        return response()->json([
            'status' => 'received',
            'message' => 'Notificação enfileirada para processamento.',
        ], 200);
    }
}
