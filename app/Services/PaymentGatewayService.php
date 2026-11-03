<?php

namespace App\Services;

use App\Jobs\RefundLatePaymentJob;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\Payments\GatewayCharge;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentGatewayException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Orquestra a cobrança no provedor e aplica, sob locks, as transições de pedido,
 * pagamento e reserva definidas no contrato #29 (seção 2).
 */
class PaymentGatewayService
{
    /** Eventos de cobrança transparente cujo estado é conferido no provedor. */
    private const CHARGE_EVENTS = [
        'transparent.completed',
        'transparent.refunded',
        'transparent.disputed',
        'transparent.lost',
    ];

    /** Margem para o PIX expirar antes da reserva e reduzir pagamentos tardios. */
    private const PIX_EXPIRY_MARGIN_SECONDS = 60;

    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ReservationService $reservations,
    ) {
    }

    /**
     * Cria (ou devolve) a cobrança PIX do pedido. Executa fora da transação de reserva.
     */
    public function startPayment(Order $order): Payment
    {
        $existing = Payment::query()->where('order_id', $order->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        $order->refresh();
        $secondsLeft = $order->reservation_expires_at === null
            ? 0
            : $order->reservation_expires_at->getTimestamp() - now()->getTimestamp() - self::PIX_EXPIRY_MARGIN_SECONDS;

        if ($order->status !== 'pendente' || $secondsLeft < 60) {
            throw new CheckoutException('A reserva deste pedido expirou. Monte o carrinho novamente para gerar um novo pagamento.');
        }

        try {
            $charge = $this->gateway->createPixCharge($order, $secondsLeft);
        } catch (PaymentGatewayException $exception) {
            if ($exception->definitive) {
                $this->reservations->cancelPendingOrder($order->id, 'gateway_failure');
            }

            throw $exception;
        }

        $payment = DB::transaction(function () use ($order, $charge): Payment {
            Order::query()->lockForUpdate()->findOrFail($order->id);

            return Payment::query()->firstOrCreate(
                ['idempotency_key' => $this->gateway->name().':'.$order->order_number],
                [
                    'order_id' => $order->id,
                    'gateway' => $this->gateway->name(),
                    'method' => 'PIX',
                    'transaction_id' => $charge->id,
                    'status' => $charge->status,
                    'dev_mode' => $charge->devMode,
                    'amount' => $order->total_amount,
                    'payload' => $charge->safePayload,
                    'br_code' => $charge->brCode,
                    'br_code_base64' => $charge->brCodeBase64,
                    'expires_at' => $charge->expiresAt,
                ]
            );
        });

        if ($charge->amountCents !== null && $charge->amountCents !== $order->totalCents()) {
            Log::error('Cobrança criada com valor divergente do pedido.', ['order' => $order->order_number]);
            $payment->update(['status' => GatewayCharge::FAILED, 'last_error' => 'Valor divergente do pedido.']);
            $this->reservations->cancelPendingOrder($order->id, 'gateway_failure');

            throw new PaymentGatewayException('O gateway gerou uma cobrança inconsistente.', definitive: true);
        }

        return $payment;
    }

    /**
     * Processa um evento de webhook já autenticado e persistido. Idempotente por event_id.
     */
    public function handleEvent(PaymentEvent $event): string
    {
        if ($event->processed_at !== null) {
            return (string) $event->result;
        }

        $result = $this->resolveEvent($event);
        $event->update(['result' => $result, 'processed_at' => now()]);

        return $result;
    }

    /**
     * Consulta o provedor e aplica o estado atual da cobrança (botão "atualizar" e reconciliação).
     */
    public function syncPayment(Payment $payment): string
    {
        return $this->applyCharge($payment, $this->gateway->fetchCharge($payment->transaction_id));
    }

    public function simulatePayment(Payment $payment): string
    {
        if (! config('services.abacatepay.dev_mode') || ! $payment->dev_mode) {
            throw new CheckoutException('A simulação de pagamento só está disponível em modo de desenvolvimento.');
        }

        $this->gateway->simulatePayment($payment->transaction_id);

        return $this->syncPayment($payment);
    }

    /**
     * Estorno de pagamento tardio (D06). O provedor rejeita estorno repetido.
     */
    public function refundLatePayment(Payment $payment): void
    {
        $payment->refresh();

        if (! in_array($payment->refund_status, ['pending', 'failed'], true)) {
            return;
        }

        try {
            $refund = $this->gateway->refund(
                $payment->transaction_id,
                "Pagamento recebido após a expiração da reserva do pedido {$payment->order?->order_number}."
            );
        } catch (PaymentGatewayException $exception) {
            $payment->update([
                'refund_status' => 'failed',
                'refund_attempts' => $payment->refund_attempts + 1,
                'last_error' => mb_substr($exception->getMessage(), 0, 255),
            ]);

            throw $exception;
        }

        $payment->update([
            'refund_status' => 'refunded',
            'refund_id' => $refund->id ?? $payment->refund_id,
            'refund_attempts' => $payment->refund_attempts + 1,
            'last_error' => null,
        ]);
    }

    /**
     * Aplica o estado verificado no provedor sob locks de pedido, anúncios e reservas.
     */
    public function applyCharge(Payment $payment, GatewayCharge $charge): string
    {
        if ($charge->id !== $payment->transaction_id) {
            throw new RuntimeException('Cobrança consultada não corresponde ao pagamento local.');
        }

        $refundLate = false;

        $result = DB::transaction(function () use ($payment, $charge, &$refundLate): string {
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $reservations = $this->reservations->lockReservations($order);

            return match ($charge->status) {
                GatewayCharge::PAID => $this->confirm($order, $payment, $reservations, $charge, $refundLate),
                GatewayCharge::EXPIRED, GatewayCharge::CANCELLED, GatewayCharge::FAILED => $this->fail($order, $payment, $reservations, $charge),
                GatewayCharge::REFUNDED, GatewayCharge::DISPUTED => $this->recordAfterPayment($order, $payment, $charge),
                default => 'pending',
            };
        }, 3);

        if ($refundLate) {
            try {
                RefundLatePaymentJob::dispatch($payment->id);
            } catch (PaymentGatewayException $exception) {
                // Com fila síncrona a falha chega aqui; refund_status já registra a pendência
                // e payments:reconcile reenvia o estorno.
                report($exception);
            }
        }

        return $result;
    }

    private function resolveEvent(PaymentEvent $event): string
    {
        if (! in_array($event->event_type, self::CHARGE_EVENTS, true) || $event->charge_id === null) {
            return 'ignored';
        }

        $payment = Payment::query()
            ->where('gateway', $event->gateway)
            ->where('transaction_id', $event->charge_id)
            ->first();

        if ($payment === null) {
            Log::warning('Webhook para cobrança desconhecida.', ['event' => $event->event_id]);

            return 'unknown_charge';
        }

        // O corpo do webhook nunca é fonte de verdade do status: consultamos o provedor.
        $charge = $this->gateway->fetchCharge($payment->transaction_id);
        $eventAmount = data_get($event->payload, 'amount');

        return $this->applyCharge($payment, $charge->withAmount(is_numeric($eventAmount) ? (int) $eventAmount : null));
    }

    private function confirm(Order $order, Payment $payment, Collection $reservations, GatewayCharge $charge, bool &$refundLate): string
    {
        if ($order->isPaid()) {
            if ($payment->status !== GatewayCharge::PAID) {
                $payment->update(['status' => GatewayCharge::PAID, 'paid_at' => $payment->paid_at ?? now()]);
            }

            return 'already_confirmed';
        }

        $itemCount = $order->items()->count();
        $valid = $order->status === 'pendente'
            && $reservations->count() === $itemCount
            && $reservations->every(fn ($reservation): bool => $reservation->isHolding())
            && ($charge->amountCents === null || $charge->amountCents === $order->totalCents());

        if (! $valid) {
            if ($payment->refund_status !== null) {
                return 'late_payment';
            }

            // Pagamento tardio ou inconsistente: não readquire saldo nem entrega (D06, D13).
            $this->reservations->releaseActive($reservations, 'late_payment');
            if ($order->status === 'pendente') {
                $order->update(['status' => 'cancelado']);
            }
            $payment->update([
                'status' => GatewayCharge::PAID,
                'paid_at' => $payment->paid_at ?? now(),
                'refund_status' => 'pending',
            ]);
            $refundLate = true;

            Log::warning('Pagamento tardio encaminhado para estorno.', ['order' => $order->order_number]);

            return 'late_payment';
        }

        $listings = Listing::query()->whereIn('id', $reservations->pluck('listing_id'))->get()->keyBy('id');

        foreach ($reservations as $reservation) {
            $listing = $listings->get($reservation->listing_id);
            $remaining = $listing->capacity - $reservation->quantity;

            if ($remaining < 0) {
                throw new RuntimeException("Capacidade negativa ao confirmar o anúncio {$listing->id}.");
            }

            $attributes = ['capacity' => $remaining];
            if ($remaining === 0 && in_array($listing->status, ['publicado', 'pausado'], true)) {
                $attributes['status'] = 'vendido';
            }

            $listing->update($attributes);
            $reservation->update(['status' => 'confirmed']);
        }

        $order->update(['status' => 'pago']);
        $order->items()->where('delivery_status', 'aguardando_pagamento')->update(['delivery_status' => 'em_entrega']);
        $payment->update(['status' => GatewayCharge::PAID, 'paid_at' => now(), 'last_error' => null]);

        Log::info('Pagamento confirmado no provedor.', ['order' => $order->order_number]);

        return 'confirmed';
    }

    private function fail(Order $order, Payment $payment, Collection $reservations, GatewayCharge $charge): string
    {
        // Pagamento confirmado nunca regride por evento antigo ou fora de ordem (D09).
        if ($order->isPaid() || $payment->status === GatewayCharge::PAID) {
            return 'ignored_regression';
        }

        $payment->update(['status' => $charge->status]);

        if ($order->status === 'pendente') {
            $this->reservations->releaseActive($reservations, $charge->status);
            $order->update(['status' => 'cancelado']);
        }

        return 'released';
    }

    private function recordAfterPayment(Order $order, Payment $payment, GatewayCharge $charge): string
    {
        if ($payment->status === GatewayCharge::PAID || $payment->status === GatewayCharge::DISPUTED) {
            $payment->update(['status' => $charge->status]);
        }

        if ($charge->status === GatewayCharge::REFUNDED && $payment->refund_status !== null) {
            $payment->update(['refund_status' => 'refunded']);
        }

        // Estorno/disputa após confirmação não repõe saldo: exige reconciliação auditável.
        if ($order->isPaid()) {
            Log::warning('Pagamento confirmado foi estornado ou contestado no provedor.', [
                'order' => $order->order_number,
                'status' => $charge->status,
            ]);
        }

        return 'recorded_'.$charge->status;
    }
}
