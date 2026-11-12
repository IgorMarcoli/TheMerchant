<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\Payments\AbacatePayGateway;
use App\Services\Payments\GatewayCharge;
use App\Services\Payments\PaymentGatewayException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Throwable;

class AbacatePayGatewayTest extends TestCase
{
    private AbacatePayGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.abacatepay.api_key' => 'abc_dev_chave',
            'services.abacatepay.base_url' => 'https://api.abacatepay.com/v2',
        ]);
        Http::preventStrayRequests();
        $this->gateway = new AbacatePayGateway();
    }

    public function test_creates_pix_charge_in_cents_with_order_number_as_idempotent_reference(): void
    {
        Http::fake(['*/transparents/create' => Http::response(['data' => [
            'id' => 'pix_char_1', 'amount' => 15050, 'status' => 'PENDING', 'devMode' => true,
            'brCode' => '000201', 'brCodeBase64' => 'data:image/png;base64,AAA', 'expiresAt' => '2026-09-25T12:14:00.000Z',
            'customer' => ['name' => 'Não deve ser guardado'],
        ], 'success' => true, 'error' => null])]);

        $order = new Order(['order_number' => 'ORD-ABC-20260925', 'total_amount' => '150.50']);
        $charge = $this->gateway->createPixCharge($order, 840);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.abacatepay.com/v2/transparents/create'
            && $request->hasHeader('Authorization', 'Bearer abc_dev_chave')
            && $request->data() === ['method' => 'PIX', 'data' => [
                'amount' => 15050,
                'expiresIn' => 840,
                'description' => 'Pedido ORD-ABC-20260925 - TheMerchant',
                'externalId' => 'ORD-ABC-20260925',
                'metadata' => ['order_number' => 'ORD-ABC-20260925'],
            ]]);

        // A AbacatePay responde 400 a caracteres fora do ASCII na descrição (ex.: travessão U+2014).
        Http::assertSent(fn (Request $request): bool => mb_check_encoding($request->data()['data']['description'], 'ASCII'));

        $this->assertSame('pix_char_1', $charge->id);
        $this->assertSame(GatewayCharge::PENDING, $charge->status);
        $this->assertSame(15050, $charge->amountCents);
        $this->assertTrue($charge->devMode);
        $this->assertArrayNotHasKey('customer', $charge->safePayload);
    }

    public function test_maps_provider_statuses(): void
    {
        $expected = [
            'PAID' => GatewayCharge::PAID,
            'APPROVED' => GatewayCharge::PAID,
            'EXPIRED' => GatewayCharge::EXPIRED,
            'CANCELLED' => GatewayCharge::CANCELLED,
            'FAILED' => GatewayCharge::FAILED,
            'REDEEMED' => GatewayCharge::FAILED,
            'REFUNDED' => GatewayCharge::REFUNDED,
            'UNDER_DISPUTE' => GatewayCharge::DISPUTED,
        ];

        $current = null;
        Http::fake(['*/transparents/check*' => function () use (&$current) {
            return Http::response(['data' => ['id' => 'pix_char_1', 'status' => $current], 'success' => true, 'error' => null]);
        }]);

        foreach ($expected as $providerStatus => $status) {
            $current = $providerStatus;
            $this->assertSame($status, $this->gateway->fetchCharge('pix_char_1')->status, $providerStatus);
        }
    }

    public function test_unknown_status_is_a_transient_error_never_a_confirmation(): void
    {
        Http::fake(['*' => Http::response(['data' => ['id' => 'pix_char_1', 'status' => 'MYSTERY'], 'success' => true])]);

        $this->assertGatewayFailure(fn () => $this->gateway->fetchCharge('pix_char_1'), definitive: false);
    }

    public function test_classifies_failures_as_definitive_or_transient(): void
    {
        $response = null;
        Http::fake(['*' => function () use (&$response) {
            return $response instanceof Throwable ? throw $response : $response;
        }]);

        $response = Http::response(['error' => 'Valor mínimo é R$ 1,00'], 400);
        $this->assertGatewayFailure(fn () => $this->gateway->fetchCharge('x'), definitive: true, message: 'Valor mínimo é R$ 1,00');

        $response = Http::response(['error' => 'Internal'], 500);
        $this->assertGatewayFailure(fn () => $this->gateway->fetchCharge('x'), definitive: false);

        $response = Http::response(['error' => 'Too many'], 429);
        $this->assertGatewayFailure(fn () => $this->gateway->fetchCharge('x'), definitive: false);

        $response = new ConnectionException('timeout');
        $this->assertGatewayFailure(fn () => $this->gateway->fetchCharge('x'), definitive: false);
    }

    public function test_missing_api_key_fails_definitively_without_calling_the_provider(): void
    {
        config(['services.abacatepay.api_key' => null]);
        Http::fake();

        $this->assertGatewayFailure(fn () => $this->gateway->fetchCharge('x'), definitive: true);
        Http::assertNothingSent();
    }

    public function test_refund_of_already_refunded_charge_is_treated_as_done(): void
    {
        Http::fake(['*/transparents/refund' => Http::response([
            'data' => null, 'success' => false, 'error' => 'Esta cobrança já foi reembolsada.',
        ], 400)]);

        $refund = $this->gateway->refund('pix_char_1', 'Pagamento tardio');

        $this->assertTrue($refund->alreadyRefunded);
    }

    private function assertGatewayFailure(callable $call, bool $definitive, ?string $message = null): void
    {
        try {
            $call();
            $this->fail('Era esperada uma falha do gateway.');
        } catch (PaymentGatewayException $exception) {
            $this->assertSame($definitive, $exception->definitive);
            if ($message !== null) {
                $this->assertSame($message, $exception->getMessage());
            }
        }
    }
}
