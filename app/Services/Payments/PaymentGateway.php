<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Contrato do provedor de pagamento externo. Nenhum dado de cartão trafega pela aplicação.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Cria a cobrança PIX do pedido. O número do pedido é a chave idempotente no provedor.
     */
    public function createPixCharge(Order $order, int $expiresInSeconds): GatewayCharge;

    /**
     * Consulta o estado autoritativo da cobrança diretamente no provedor.
     */
    public function fetchCharge(string $chargeId): GatewayCharge;

    /**
     * Solicita o estorno integral; repetir para cobrança já estornada não duplica o estorno.
     */
    public function refund(string $chargeId, string $reason): GatewayRefund;

    /**
     * Simula o pagamento de uma cobrança criada em modo de desenvolvimento.
     */
    public function simulatePayment(string $chargeId): void;

    /**
     * Valida a autenticidade de um webhook antes de qualquer leitura do payload.
     */
    public function verifyWebhook(Request $request): bool;
}
