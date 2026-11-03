<?php

namespace App\Services\Payments;

final class GatewayRefund
{
    public function __construct(
        public readonly ?string $id,
        public readonly bool $alreadyRefunded = false,
    ) {
    }
}
