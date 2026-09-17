<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CheckoutService;
use App\Services\PaymentGatewayService;

class CheckoutServiceTest extends TestCase
{
    public function test_checkout_service_instantiation(): void
    {
        $gatewayMock = $this->createMock(PaymentGatewayService::class);
        $checkoutService = new CheckoutService($gatewayMock);

        $this->assertInstanceOf(CheckoutService::class, $checkoutService);
    }
}
