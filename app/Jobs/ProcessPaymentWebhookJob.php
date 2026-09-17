<?php

namespace App\Jobs;

use App\Services\PaymentGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPaymentWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $payload,
        public ?string $signature = null
    ) {}

    public function handle(PaymentGatewayService $gatewayService): void
    {
        $gatewayService->processWebhook($this->payload, $this->signature);
    }
}
