<?php

namespace App\Services\Payments;

use Carbon\CarbonImmutable;

final class GatewayCharge
{
    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const EXPIRED = 'expired';

    public const CANCELLED = 'cancelled';

    public const FAILED = 'failed';

    public const REFUNDED = 'refunded';

    public const DISPUTED = 'disputed';

    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly ?int $amountCents = null,
        public readonly bool $devMode = false,
        public readonly ?string $brCode = null,
        public readonly ?string $brCodeBase64 = null,
        public readonly ?CarbonImmutable $expiresAt = null,
        public readonly array $safePayload = [],
    ) {
    }

    public function withAmount(?int $amountCents): self
    {
        return new self(
            $this->id,
            $this->status,
            $amountCents ?? $this->amountCents,
            $this->devMode,
            $this->brCode,
            $this->brCodeBase64,
            $this->expiresAt,
            $this->safePayload,
        );
    }
}
