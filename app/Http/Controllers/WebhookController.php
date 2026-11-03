<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPaymentWebhookJob;
use App\Models\PaymentEvent;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Recebe webhooks do gateway. Autentica, persiste o evento sanitizado uma única vez
     * e delega o processamento à fila; o status é sempre conferido no provedor.
     */
    public function handlePaymentWebhook(Request $request, PaymentGateway $gateway): JsonResponse
    {
        if (! $gateway->verifyWebhook($request)) {
            Log::warning('Webhook de pagamento rejeitado: autenticação inválida.', ['ip' => $request->ip()]);

            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $eventId = is_array($payload) ? ($payload['id'] ?? null) : null;
        $eventType = is_array($payload) ? ($payload['event'] ?? null) : null;

        if (! is_string($eventId) || $eventId === '' || ! is_string($eventType) || $eventType === '') {
            return response()->json(['status' => 'invalid_payload'], 400);
        }

        $chargeId = data_get($payload, 'data.transparent.id');

        $event = PaymentEvent::createOrFirst(
            ['event_id' => mb_substr($eventId, 0, 100)],
            [
                'gateway' => $gateway->name(),
                'event_type' => mb_substr($eventType, 0, 64),
                'charge_id' => is_string($chargeId) ? mb_substr($chargeId, 0, 100) : null,
                // Apenas campos operacionais: dados do cliente e do pagador são descartados.
                'payload' => [
                    'event' => $eventType,
                    'api_version' => data_get($payload, 'apiVersion'),
                    'dev_mode' => (bool) data_get($payload, 'devMode', false),
                    'status' => data_get($payload, 'data.transparent.status'),
                    'amount' => data_get($payload, 'data.transparent.amount'),
                    'external_id' => data_get($payload, 'data.transparent.externalId'),
                ],
            ]
        );

        if ($event->wasRecentlyCreated || $event->processed_at === null) {
            ProcessPaymentWebhookJob::dispatch($event->id);
        }

        return response()->json(['status' => 'received']);
    }
}
