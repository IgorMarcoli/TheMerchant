<?php

namespace Tests\Unit;

use App\Services\CheckoutService;
use App\Services\PaymentGatewayService;
use PHPUnit\Framework\TestCase;

class CheckoutServiceTest extends TestCase
{
    public function test_checkout_service_instantiation(): void
    {
        $gatewayMock = $this->createMock(PaymentGatewayService::class);
        $checkoutService = new CheckoutService($gatewayMock);

        $this->assertInstanceOf(CheckoutService::class, $checkoutService);
    }
}
