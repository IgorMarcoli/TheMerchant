<?php

namespace App\Services\Payments;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cliente da API v2 da AbacatePay (Checkout Transparente PIX).
 *
 * @see https://docs.abacatepay.com/pages/transparents/create
 */
class AbacatePayGateway implements PaymentGateway
{
    private const STATUS_MAP = [
        'PENDING' => GatewayCharge::PENDING,
        'PAID' => GatewayCharge::PAID,
        'APPROVED' => GatewayCharge::PAID,
        'EXPIRED' => GatewayCharge::EXPIRED,
        'CANCELLED' => GatewayCharge::CANCELLED,
        'FAILED' => GatewayCharge::FAILED,
        // Cupom de 100% não se aplica a pedidos do marketplace e nunca confirma venda.
        'REDEEMED' => GatewayCharge::FAILED,
        'REFUNDED' => GatewayCharge::REFUNDED,
        'UNDER_DISPUTE' => GatewayCharge::DISPUTED,
    ];

    public function name(): string
    {
        return 'abacatepay';
    }

    public function createPixCharge(Order $order, int $expiresInSeconds): GatewayCharge
    {
        $data = $this->send(fn (PendingRequest $http): Response => $http->post('/transparents/create', [
            'method' => 'PIX',
            'data' => [
                'amount' => $order->totalCents(),
                'expiresIn' => $expiresInSeconds,
                // A AbacatePay recusa caracteres como o travessão (U+2014) na descrição.
                'description' => "Pedido {$order->order_number} - TheMerchant",
                'externalId' => $order->order_number,
                'metadata' => ['order_number' => $order->order_number],
            ],
        ]));

        return $this->toCharge($data);
    }

    public function fetchCharge(string $chargeId): GatewayCharge
    {
        $data = $this->send(fn (PendingRequest $http): Response => $http->get('/transparents/check', ['id' => $chargeId]));

        return $this->toCharge($data);
    }

    public function refund(string $chargeId, string $reason): GatewayRefund
    {
        try {
            $data = $this->send(fn (PendingRequest $http): Response => $http->post('/transparents/refund', [
                'id' => $chargeId,
                'reason' => mb_substr($reason, 0, 500),
            ]));
        } catch (PaymentGatewayException $exception) {
            if (str_contains(mb_strtolower($exception->getMessage()), 'já foi reembolsada')) {
                return new GatewayRefund(null, alreadyRefunded: true);
            }

            throw $exception;
        }

        return new GatewayRefund(isset($data['id']) ? (string) $data['id'] : null);
    }

    public function simulatePayment(string $chargeId): void
    {
        $this->send(fn (PendingRequest $http): Response => $http->post('/transparents/simulate-payment?'.http_build_query(['id' => $chargeId])));
    }

    public function verifyWebhook(Request $request): bool
    {
        $secret = (string) config('services.abacatepay.webhook_secret');
        $received = $request->query('webhookSecret');

        if ($secret === '' || ! is_string($received) || ! hash_equals($secret, $received)) {
            return false;
        }

        $signature = $request->header('X-Webhook-Signature');

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac(
            'sha256',
            $request->getContent(),
            (string) config('services.abacatepay.webhook_public_key'),
            true
        ));

        return hash_equals($expected, $signature);
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     */
    private function send(callable $call): array
    {
        $apiKey = (string) config('services.abacatepay.api_key');

        if ($apiKey === '') {
            throw new PaymentGatewayException('Gateway de pagamento não configurado.', definitive: true);
        }

        $http = Http::baseUrl(rtrim((string) config('services.abacatepay.base_url'), '/'))
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout((int) config('services.abacatepay.timeout', 10));

        try {
            $response = $call($http);
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayException('Sem resposta do gateway de pagamento.', definitive: false, previous: $exception);
        } catch (Throwable $exception) {
            throw new PaymentGatewayException('Falha inesperada ao contatar o gateway.', definitive: false, previous: $exception);
        }

        $body = $response->json();
        $error = is_array($body) ? ($body['error'] ?? null) : null;

        if ($response->serverError() || in_array($response->status(), [408, 429], true)
            || (is_string($error) && str_contains($error, 'LOCK_NOT_ACQUIRED'))) {
            throw new PaymentGatewayException('Gateway de pagamento temporariamente indisponível.', definitive: false);
        }

        if (! $response->successful() || ! is_array($body) || ($body['success'] ?? false) !== true || ! is_array($body['data'] ?? null)) {
            $message = is_string($error) && $error !== '' ? $error : 'O gateway recusou a operação.';

            throw new PaymentGatewayException(mb_substr($message, 0, 200), definitive: true);
        }

        return $body['data'];
    }

    private function toCharge(array $data): GatewayCharge
    {
        if (! isset($data['id'], $data['status']) || ! is_string($data['id'])) {
            throw new PaymentGatewayException('Resposta do gateway sem identificação da cobrança.', definitive: false);
        }

        $status = self::STATUS_MAP[strtoupper((string) $data['status'])] ?? null;

        if ($status === null) {
            throw new PaymentGatewayException('Status de cobrança desconhecido retornado pelo gateway.', definitive: false);
        }

        $expiresAt = null;
        if (is_string($data['expiresAt'] ?? null)) {
            try {
                $expiresAt = CarbonImmutable::parse($data['expiresAt']);
            } catch (Throwable) {
                $expiresAt = null;
            }
        }

        return new GatewayCharge(
            id: $data['id'],
            status: $status,
            amountCents: isset($data['amount']) && is_numeric($data['amount']) ? (int) $data['amount'] : null,
            devMode: (bool) ($data['devMode'] ?? false),
            brCode: is_string($data['brCode'] ?? null) ? $data['brCode'] : null,
            brCodeBase64: is_string($data['brCodeBase64'] ?? null) ? $data['brCodeBase64'] : null,
            expiresAt: $expiresAt,
            // Somente campos operacionais; dados do pagador nunca são persistidos.
            safePayload: array_intersect_key($data, array_flip([
                'id', 'status', 'amount', 'devMode', 'platformFee', 'receiptUrl', 'createdAt', 'updatedAt', 'expiresAt',
            ])),
        );
    }
}
