<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\PaymentGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefundLatePaymentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public function __construct(
        public int $paymentId
    ) {
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(PaymentGatewayService $gatewayService): void
    {
        $payment = Payment::find($this->paymentId);

        if ($payment !== null) {
            $gatewayService->refundLatePayment($payment);
        }
    }
}
