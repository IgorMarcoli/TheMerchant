<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentGatewayService
{
    /**
     * Gera a preferência/sessão de pagamento junto ao provedor configurado.
     */
    public function createPaymentPreference(Order $order): array
    {
        $gateway = config('services.payment_gateway', 'mercadopago');

        Log::info("Criando preferência de pagamento no gateway [{$gateway}] para o pedido #{$order->order_number}");

        // Para ambiente de homologação ou desenvolvimento acadêmico
        return [
            'id'           => 'pref_' . md5($order->order_number . microtime()),
            'gateway'      => $gateway,
            'checkout_url' => route('checkout.success', $order),
            'expires_at'   => now()->addHours(24)->toIso8601String(),
        ];
    }

    /**
     * Processa a notificação recebida via Webhook com tratamento de Idempotência.
     */
    public function processWebhook(array $payload, ?string $signature = null): array
    {
        $transactionId  = $payload['data']['id'] ?? $payload['id'] ?? null;
        $status         = $payload['action'] ?? $payload['status'] ?? 'approved';
        $orderNumber    = $payload['external_reference'] ?? $payload['order_number'] ?? null;
        $idempotencyKey = "webhook_{$transactionId}_{$status}";

        if (!$transactionId) {
            return ['status' => 'ignored', 'message' => 'Nenhum transaction_id fornecido'];
        }

        // Verificação de idempotência: evita processamento duplicado
        $existingPayment = Payment::where('idempotency_key', $idempotencyKey)->first();
        if ($existingPayment) {
            Log::info("Webhook idempotente já processado anteriormente: {$idempotencyKey}");
            return ['status' => 'already_processed', 'payment' => $existingPayment];
        }

        return DB::transaction(function () use ($orderNumber, $transactionId, $status, $idempotencyKey, $payload) {
            $order = Order::where('order_number', $orderNumber)->first();

            if (!$order) {
                Log::warning("Pedido não localizado para o webhook: {$orderNumber}");
                return ['status' => 'order_not_found'];
            }

            $payment = Payment::create([
                'order_id'        => $order->id,
                'gateway'         => config('services.payment_gateway', 'mercadopago'),
                'transaction_id'  => (string) $transactionId,
                'status'          => $status,
                'amount'          => $order->total_amount,
                'idempotency_key' => $idempotencyKey,
                'payload'         => $payload,
            ]);

            // Se pagamento aprovado, transiciona status do pedido e itens
            if (in_array(strtolower($status), ['approved', 'payment.created', 'paid'])) {
                $order->update(['status' => 'pago']);
                $order->items()->update(['delivery_status' => 'em_entrega']);

                Log::info("Pedido #{$order->order_number} confirmado como PAGO via webhook.");
            }

            return ['status' => 'success', 'payment' => $payment];
        });
    }
}
