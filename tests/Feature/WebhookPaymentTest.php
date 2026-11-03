<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAbacatePay;
use Tests\TestCase;

class WebhookPaymentTest extends TestCase
{
    use InteractsWithAbacatePay;
    use RefreshDatabase;

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAbacatePay();
        $this->setUpMarketplace();
        $this->buyer = User::factory()->create();
    }

    public function test_webhook_without_valid_secret_is_rejected_and_nothing_is_stored(): void
    {
        $order = $this->paidChargeOrder();
        $event = $this->completedEvent($order->payment->transaction_id);

        $this->postWebhook($event, secret: null)->assertUnauthorized();
        $this->postWebhook($event, secret: 'segredo-errado')->assertUnauthorized();

        config(['services.abacatepay.webhook_secret' => null]);
        $this->postWebhook($event, secret: '')->assertUnauthorized();

        $this->assertDatabaseCount('payment_events', 0);
        $this->assertSame('pendente', $order->fresh()->status);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $order = $this->paidChargeOrder();
        $event = $this->completedEvent($order->payment->transaction_id);

        $this->postWebhook($event, signature: base64_encode('assinatura-falsa'))->assertUnauthorized();
        $this->postWebhook($event, signature: '')->assertUnauthorized();

        $this->assertDatabaseCount('payment_events', 0);
        $this->assertSame('pendente', $order->fresh()->status);
    }

    public function test_forged_order_reference_cannot_mark_an_order_as_paid(): void
    {
        $order = $this->paidChargeOrder(markPaid: false);

        // Payload no formato antigo, sem segredo nem assinatura: antes marcava o pedido como pago.
        $this->postJson(route('api.webhooks.payment'), [
            'id' => '999',
            'order_number' => $order->order_number,
            'external_reference' => $order->order_number,
        ])->assertUnauthorized();

        // Mesmo autenticado, uma cobrança desconhecida não altera pedidos.
        $this->postWebhook($this->completedEvent('pix_char_inexistente'))->assertOk();

        $this->assertSame('unknown_charge', PaymentEvent::sole()->result);
        $this->assertSame('pendente', $order->fresh()->status);
    }

    public function test_valid_webhook_is_stored_sanitized_and_processed_once(): void
    {
        $order = $this->paidChargeOrder();
        $event = $this->completedEvent($order->payment->transaction_id, 'log_abc123');

        $this->postWebhook($event)->assertOk()->assertJson(['status' => 'received']);
        $this->postWebhook($event)->assertOk();

        $stored = PaymentEvent::sole();
        $this->assertSame('confirmed', $stored->result);
        $this->assertNotNull($stored->processed_at);
        $this->assertSame('transparent.completed', $stored->event_type);
        $this->assertSame(2000, $stored->payload['amount']);

        $raw = json_encode($stored->payload);
        $this->assertStringNotContainsString('Maria', $raw);
        $this->assertStringNotContainsString('taxId', $raw);
        $this->assertStringNotContainsString('maria@exemplo.com', $raw);

        $this->assertSame('pago', $order->fresh()->status);
        $this->assertSame(1, $this->gatewayRequests('/transparents/check'));
    }

    public function test_amount_divergent_from_order_is_never_confirmed(): void
    {
        $order = $this->paidChargeOrder();
        $event = $this->completedEvent($order->payment->transaction_id);
        $event['data']['transparent']['amount'] = 100;

        $this->postWebhook($event)->assertOk();

        $this->assertSame('cancelado', $order->fresh()->status);
        $this->assertSame('refunded', $order->payment->fresh()->refund_status);
    }

    public function test_unhandled_events_are_acknowledged_without_side_effects(): void
    {
        $this->postWebhook([
            'id' => 'log_payout',
            'event' => 'payout.completed',
            'apiVersion' => 2,
            'devMode' => true,
            'data' => [],
        ])->assertOk();

        $this->assertSame('ignored', PaymentEvent::sole()->result);
    }

    public function test_malformed_payload_is_rejected(): void
    {
        $this->postWebhook(['event' => 'transparent.completed'])->assertStatus(400);
        $this->assertDatabaseCount('payment_events', 0);
    }

    private function paidChargeOrder(bool $markPaid = true): Order
    {
        $this->addToCart($this->buyer, $this->cosmetic('Skin de teste', 20.00));
        $this->checkout($this->buyer);
        $order = $this->lastOrderOf($this->buyer)->load('payment');

        if ($markPaid) {
            $this->setChargeStatus($order->payment->transaction_id, 'PAID');
        }

        return $order;
    }
}
