<?php

namespace App\Jobs;

use App\Models\PaymentEvent;
use App\Services\PaymentGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPaymentWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public function __construct(
        public int $paymentEventId
    ) {
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(PaymentGatewayService $gatewayService): void
    {
        $event = PaymentEvent::find($this->paymentEventId);

        if ($event !== null) {
            $gatewayService->handleEvent($event);
        }
    }
}
