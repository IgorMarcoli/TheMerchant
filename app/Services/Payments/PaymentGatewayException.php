<?php

namespace App\Services\Payments;

use RuntimeException;
use Throwable;

/**
 * Falha na comunicação com o provedor.
 * Definitiva: o provedor recusou a operação. Transitória: resultado desconhecido (timeout, 5xx),
 * que exige nova tentativa idempotente ou reconciliação, nunca suposição de sucesso.
 */
class PaymentGatewayException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $definitive,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
