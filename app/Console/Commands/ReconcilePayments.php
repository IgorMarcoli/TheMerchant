<?php

namespace App\Console\Commands;

use App\Jobs\RefundLatePaymentJob;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use App\Services\Payments\PaymentGatewayException;
use App\Services\ReservationService;
use Illuminate\Console\Command;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile';

    protected $description = 'Consulta cobranças pendentes no provedor, expira reservas vencidas e reenvia estornos pendentes';

    public function handle(PaymentGatewayService $payments, ReservationService $reservations): int
    {
        // 1. Webhook perdido: o provedor é consultado antes de expirar a reserva.
        $synced = 0;
        Payment::query()
            ->where('status', 'pending')
            ->whereHas('order', fn ($order) => $order->where('status', 'pendente'))
            ->orderBy('id')
            ->each(function (Payment $payment) use ($payments, &$synced): void {
                try {
                    $payments->syncPayment($payment);
                    $synced++;
                } catch (PaymentGatewayException $exception) {
                    $this->warn("Cobrança {$payment->transaction_id}: {$exception->getMessage()}");
                }
            });

        // 2. Reservas vencidas liberam o saldo, sem consumir capacidade (D05).
        $expired = $reservations->expireDueOrders();

        // 3. Estornos de pagamentos tardios que falharam voltam para a fila (D06).
        $refunds = 0;
        Payment::query()->whereIn('refund_status', ['pending', 'failed'])->pluck('id')
            ->each(function (int $paymentId) use (&$refunds): void {
                RefundLatePaymentJob::dispatch($paymentId);
                $refunds++;
            });

        $this->info("Cobranças consultadas: {$synced}. Pedidos expirados: {$expired}. Estornos reenviados: {$refunds}.");

        return self::SUCCESS;
    }
}
