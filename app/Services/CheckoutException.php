<?php

namespace App\Services;

use RuntimeException;

/**
 * Regra de negócio do checkout violada; a mensagem é segura para exibição ao comprador.
 */
class CheckoutException extends RuntimeException
{
}
