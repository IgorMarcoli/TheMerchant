<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithAbacatePay;
use Tests\TestCase;

/**
 * Cenários D01–D14 do contrato de domínio (docs/CONTRATO_DOMINIO.md) para #13 e #14.
 */
class CheckoutReservationTest extends TestCase
{
    use InteractsWithAbacatePay;
    use RefreshDatabase;

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAbacatePay();
        $this->setUpMarketplace();
        $this->buyer = User::factory()->create(['name' => 'Comprador']);
        $this->travelTo(Carbon::parse('2026-09-25 12:00:00', 'UTC'));
    }

    public function test_d01_cosmetic_is_reserved_at_checkout_and_sold_only_after_verified_payment(): void
    {
        $listing = $this->cosmetic('AK-47 Redline', 80.00);
        $this->addToCart($this->buyer, $listing);

        $order = $this->checkoutOrFail($this->buyer);

        $this->assertSame('pendente', $order->status);
        $this->assertSame('80.00', $order->total_amount);
        $this->assertTrue($order->reservation_expires_at->equalTo(now()->addMinutes(15)));
        $this->assertSame(1, $listing->fresh()->capacity);
        $this->assertSame('publicado', $listing->fresh()->status);
        $this->assertSame(0, $listing->fresh()->availableQuantity());
        $this->assertDatabaseCount('cart_items', 0);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/transparents/create')
            && $request->hasHeader('Authorization', 'Bearer abc_dev_chave_de_teste')
            && $request['method'] === 'PIX'
            && $request['data']['amount'] === 8000
            && $request['data']['externalId'] === $order->order_number
            && $request['data']['expiresIn'] === 840);

        $payment = $order->payment;
        $this->assertSame('pending', $payment->status);
        $this->assertNotNull($payment->br_code);

        $this->travel(5)->minutes();
        $this->setChargeStatus($payment->transaction_id, 'PAID');
        $this->postWebhook($this->completedEvent($payment->transaction_id))->assertOk();

        $listing->refresh();
        $order->refresh();
        $this->assertSame('pago', $order->status);
        $this->assertSame(0, $listing->capacity);
        $this->assertSame('vendido', $listing->status);
        $this->assertSame('confirmed', Reservation::sole()->status);
        $this->assertSame('em_entrega', $order->items()->sole()->delivery_status);
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_d02_service_reserves_sessions_and_payment_consumes_only_the_purchased_quantity(): void
    {
        $listing = $this->service('Coaching tático', 50.00, capacity: 5, duration: 60);
        $this->addToCart($this->buyer, $listing, quantity: 3);

        $order = $this->checkoutOrFail($this->buyer);
        $this->assertSame(2, $listing->fresh()->availableQuantity());
        $this->assertSame('150.00', $order->total_amount);

        $this->payViaWebhook($order);

        $listing->refresh();
        $this->assertSame(2, $listing->capacity);
        $this->assertSame('publicado', $listing->status);
        $this->assertSame(2, $listing->availableQuantity());

        $item = $order->items()->sole();
        $this->assertSame(3, $item->quantity);
        $this->assertSame(60, $item->session_duration_minutes);
        $this->assertSame('service', $item->listing_type);
        $this->assertSame('by_chat', $item->scheduling_mode);
        $this->assertSame('America/Sao_Paulo', $item->timezone);
        $this->assertSame('BRL', $item->currency);
    }

    public function test_d03_quantity_above_capacity_is_rejected_without_order_reservation_or_charge(): void
    {
        $listing = $this->service('Mentoria', 50.00, capacity: 2);
        $this->addToCart($this->buyer, $listing, quantity: 3);

        $this->checkout($this->buyer)->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('reservations', 0);
        $this->assertSame(0, $this->gatewayRequests('/transparents/create'));
        $this->assertSame(2, $listing->fresh()->capacity);
    }

    public function test_d04_second_buyer_cannot_reserve_a_cosmetic_already_reserved(): void
    {
        $listing = $this->cosmetic();
        $other = User::factory()->create();
        $this->addToCart($this->buyer, $listing);
        $this->addToCart($other, $listing);

        $this->checkoutOrFail($this->buyer);
        $this->checkout($other)->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'Quantidade indisponível'));

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(1, Reservation::count());
        $this->assertSame(1, $this->gatewayRequests('/transparents/create'));
        $this->assertSame(1, $listing->fresh()->capacity);
    }

    public function test_d05_expired_reservation_stops_blocking_immediately_and_is_released_idempotently(): void
    {
        $listing = $this->service(capacity: 5);
        $this->addToCart($this->buyer, $listing, quantity: 3);
        $order = $this->checkoutOrFail($this->buyer);

        $this->travelTo(now()->addMinutes(15));
        $this->assertSame(5, $listing->fresh()->availableQuantity(), 'Em now == expires_at a reserva já expirou.');

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame('cancelado', $order->fresh()->status);
        $this->assertSame('released', Reservation::sole()->status);
        $this->assertSame('expired', Reservation::sole()->released_reason);
        $this->assertSame('expired', $order->payment->fresh()->status);
        $this->assertSame(5, $listing->fresh()->capacity);

        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertSame(5, $listing->fresh()->capacity);
        $this->assertSame('released', Reservation::sole()->status);
    }

    public function test_d06_late_payment_after_capacity_was_sold_again_is_refunded_without_consuming_stock(): void
    {
        $listing = $this->service(capacity: 5);
        $this->addToCart($this->buyer, $listing, quantity: 3);
        $lateOrder = $this->checkoutOrFail($this->buyer);

        $this->travelTo(now()->addMinutes(15));
        $this->artisan('payments:reconcile')->assertSuccessful();

        $other = User::factory()->create();
        $this->addToCart($other, $listing, quantity: 5);
        $newOrder = $this->checkoutOrFail($other);
        $this->payViaWebhook($newOrder);
        $this->assertSame(0, $listing->fresh()->capacity);

        $this->travel(1)->minutes();
        $latePayment = $lateOrder->payment()->sole();
        $this->setChargeStatus($latePayment->transaction_id, 'PAID');
        $this->postWebhook($this->completedEvent($latePayment->transaction_id))->assertOk();

        $latePayment->refresh();
        $this->assertSame('cancelado', $lateOrder->fresh()->status);
        $this->assertSame('paid', $latePayment->status);
        $this->assertSame('refunded', $latePayment->refund_status);
        $this->assertSame('aguardando_pagamento', $lateOrder->items()->sole()->delivery_status);
        $this->assertSame(0, $listing->fresh()->capacity);
        $this->assertSame('pago', $newOrder->fresh()->status);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/transparents/refund')
            && $request['id'] === $latePayment->transaction_id);

        // Webhook repetido do pagamento tardio não gera segundo estorno.
        $this->postWebhook($this->completedEvent($latePayment->transaction_id))->assertOk();
        $this->assertSame(1, $this->gatewayRequests('/transparents/refund'));
    }

    public function test_d09_confirmed_payment_never_regresses_on_duplicate_or_stale_events(): void
    {
        $listing = $this->cosmetic();
        $this->addToCart($this->buyer, $listing);
        $order = $this->checkoutOrFail($this->buyer);
        $chargeId = $order->payment->transaction_id;
        $this->setChargeStatus($chargeId, 'PAID');

        $event = $this->completedEvent($chargeId, 'log_evento_unico');
        $this->postWebhook($event)->assertOk();
        $this->postWebhook($event)->assertOk();
        $this->assertDatabaseCount('payment_events', 1);
        $this->assertSame(1, $this->gatewayRequests('/transparents/check'));

        $this->postWebhook($this->completedEvent($chargeId))->assertOk();

        // Evento antigo de expiração depois da confirmação.
        $this->setChargeStatus($chargeId, 'EXPIRED');
        $this->postWebhook($this->completedEvent($chargeId))->assertOk();
        $this->travelTo(now()->addHour());
        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame('pago', $order->fresh()->status);
        $this->assertSame('paid', $order->payment->fresh()->status);
        $this->assertSame(0, $listing->fresh()->capacity);
        $this->assertSame('confirmed', Reservation::sole()->status);
    }

    public function test_d10_order_item_keeps_the_offer_snapshot_after_the_listing_is_edited(): void
    {
        $listing = $this->service('Coaching de posicionamento', 40.00, capacity: 4, duration: 45);
        $this->addToCart($this->buyer, $listing, quantity: 2);
        $order = $this->checkoutOrFail($this->buyer);

        $listing->update([
            'price' => 99.90,
            'session_duration_minutes' => 90,
            'delivery_conditions' => 'Novas condições.',
            'timezone' => 'Europe/Lisbon',
            'title' => 'Título novo',
        ]);

        $item = $order->items()->sole();
        $this->assertSame('40.00', $item->unit_price);
        $this->assertSame(45, $item->session_duration_minutes);
        $this->assertSame('Sessões combinadas pelo chat.', $item->delivery_conditions);
        $this->assertSame('America/Sao_Paulo', $item->timezone);
        $this->assertSame('Coaching de posicionamento', $item->listing_title);
        $this->assertSame('CS2', $item->game_name);
        $this->assertSame('Coaching', $item->category_name);
        $this->assertSame('80.00', $order->fresh()->total_amount);
    }

    public function test_d11_ineligible_offer_is_rejected_at_checkout(): void
    {
        $listing = $this->cosmetic();
        $this->addToCart($this->buyer, $listing);
        $this->cosmeticCategory->update(['is_active' => false]);

        $this->checkout($this->buyer)->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'não está mais disponível'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_d12_one_unavailable_item_rolls_back_the_whole_checkout(): void
    {
        $available = $this->cosmetic('Item disponível');
        $unavailable = $this->service('Serviço esgotado', capacity: 2);
        $this->addToCart($this->buyer, $available);
        $this->addToCart($this->buyer, $unavailable);
        $unavailable->update(['capacity' => 0, 'status' => 'vendido']);

        $this->checkout($this->buyer)->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('reservations', 0);
        $this->assertDatabaseCount('cart_items', 2);
        $this->assertSame(0, $this->gatewayRequests('/transparents/create'));
    }

    public function test_d13_approval_processed_exactly_at_expiration_is_treated_as_late(): void
    {
        $listing = $this->cosmetic();
        $this->addToCart($this->buyer, $listing);
        $order = $this->checkoutOrFail($this->buyer);
        $chargeId = $order->payment->transaction_id;

        $this->travelTo($order->reservation_expires_at);
        $this->setChargeStatus($chargeId, 'PAID');
        $this->postWebhook($this->completedEvent($chargeId))->assertOk();

        $this->assertSame('cancelado', $order->fresh()->status);
        $this->assertSame('refunded', $order->payment->fresh()->refund_status);
        $this->assertSame(1, $listing->fresh()->capacity);
        $this->assertSame('publicado', $listing->fresh()->status);
    }

    public function test_d14_paused_offer_keeps_existing_reservation_but_blocks_new_purchases(): void
    {
        $listing = $this->service(capacity: 3);
        $this->addToCart($this->buyer, $listing, quantity: 2);
        $order = $this->checkoutOrFail($this->buyer);

        $other = User::factory()->create();
        $this->addToCart($other, $listing);
        $listing->update(['status' => 'pausado']);

        $this->checkout($other)->assertRedirect(route('cart.index'))->assertSessionHas('error');
        $this->assertSame('active', Reservation::sole()->status);

        $this->payViaWebhook($order);

        $listing->refresh();
        $this->assertSame('pago', $order->fresh()->status);
        $this->assertSame(1, $listing->capacity);
        $this->assertSame('pausado', $listing->status);
    }

    public function test_resubmitting_the_same_checkout_key_returns_the_same_order(): void
    {
        $this->addToCart($this->buyer, $this->cosmetic());
        $key = (string) Str::uuid();

        $first = $this->checkout($this->buyer, $key);
        $second = $this->checkout($this->buyer, $key);

        $order = Order::sole();
        $first->assertRedirect(route('checkout.success', $order));
        $second->assertRedirect(route('checkout.success', $order));
        $this->assertSame(1, $this->gatewayRequests('/transparents/create'));
    }

    public function test_definitive_gateway_refusal_releases_reservation_and_restores_cart(): void
    {
        $listing = $this->cosmetic();
        $this->addToCart($this->buyer, $listing);
        $this->nextCreateFailure = 'definitive';

        $this->checkout($this->buyer)->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'voltaram ao carrinho'));

        $this->assertSame('cancelado', Order::sole()->status);
        $this->assertSame('released', Reservation::sole()->status);
        $this->assertSame('gateway_failure', Reservation::sole()->released_reason);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame($listing->id, CartItem::sole()->listing_id);
        $this->assertSame(1, $listing->fresh()->availableQuantity());
    }

    public function test_transient_gateway_failure_keeps_reservation_and_allows_retry(): void
    {
        $listing = $this->cosmetic();
        $this->addToCart($this->buyer, $listing);
        $this->nextCreateFailure = 'transient';

        $order = $this->checkoutOrFail($this->buyer);
        $this->assertSame('pendente', $order->status);
        $this->assertSame('active', Reservation::sole()->status);
        $this->assertDatabaseCount('payments', 0);

        $this->actingAs($this->buyer)->get(route('checkout.success', $order))
            ->assertOk()->assertSee('Gerar PIX novamente');

        $this->actingAs($this->buyer)->post(route('checkout.payment.retry', $order))
            ->assertRedirect(route('checkout.success', $order))->assertSessionHas('success');

        $this->assertSame('pending', Payment::sole()->status);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/transparents/create')
            && $request['data']['externalId'] === $order->order_number);
    }

    public function test_changed_price_updates_cart_and_asks_buyer_to_review(): void
    {
        $listing = $this->cosmetic('Karambit', 100.00);
        $this->addToCart($this->buyer, $listing);
        $listing->update(['price' => 120.00]);

        $this->checkout($this->buyer)->assertRedirect(route('cart.index'))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'foi atualizado'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('120.00', CartItem::sole()->unit_price);
    }

    public function test_webhook_body_is_not_trusted_when_provider_still_reports_pending(): void
    {
        $this->addToCart($this->buyer, $this->cosmetic());
        $order = $this->checkoutOrFail($this->buyer);

        // Corpo diz PAID, mas a consulta autoritativa ao provedor segue PENDING.
        $this->postWebhook($this->completedEvent($order->payment->transaction_id))->assertOk();

        $this->assertSame('pendente', $order->fresh()->status);
        $this->assertSame('pending', $order->payment->fresh()->status);
        $this->assertSame('active', Reservation::sole()->status);
    }

    public function test_buyer_can_refresh_status_from_provider_without_webhook(): void
    {
        $this->addToCart($this->buyer, $this->cosmetic());
        $order = $this->checkoutOrFail($this->buyer);
        $this->setChargeStatus($order->payment->transaction_id, 'PAID');

        $this->actingAs($this->buyer)->post(route('checkout.payment.refresh', $order))
            ->assertRedirect(route('checkout.success', $order));

        $this->assertSame('pago', $order->fresh()->status);
    }

    public function test_dev_mode_simulation_confirms_payment_only_when_enabled(): void
    {
        $this->addToCart($this->buyer, $this->cosmetic());
        $order = $this->checkoutOrFail($this->buyer);

        $this->actingAs($this->buyer)->post(route('checkout.payment.simulate', $order))->assertNotFound();
        $this->assertSame('pendente', $order->fresh()->status);

        config(['services.abacatepay.dev_mode' => true]);
        $this->actingAs($this->buyer)->get(route('checkout.success', $order))
            ->assertOk()->assertSee('PIX copia e cola')->assertSee('Simular pagamento');

        $this->actingAs($this->buyer)->post(route('checkout.payment.simulate', $order))
            ->assertRedirect(route('checkout.success', $order));

        $this->assertSame('pago', $order->fresh()->status);
    }

    public function test_other_users_cannot_operate_the_payment_of_an_order(): void
    {
        $this->addToCart($this->buyer, $this->cosmetic());
        $order = $this->checkoutOrFail($this->buyer);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->post(route('checkout.payment.refresh', $order))->assertForbidden();
        $this->actingAs($intruder)->post(route('checkout.payment.retry', $order))->assertForbidden();
        $this->actingAs($intruder)->post(route('checkout.payment.simulate', $order))->assertForbidden();
        $this->actingAs($this->seller)->post(route('checkout.payment.refresh', $order))->assertForbidden();
    }

    public function test_refund_failure_does_not_block_late_payment_processing_and_is_retried(): void
    {
        $listing = $this->cosmetic();
        $this->addToCart($this->buyer, $listing);
        $order = $this->checkoutOrFail($this->buyer);
        $payment = $order->payment;
        $this->travelTo($order->reservation_expires_at->addMinute());
        $this->setChargeStatus($payment->transaction_id, 'PAID');
        $this->refundUnavailable = true;

        $this->postWebhook($this->completedEvent($payment->transaction_id))->assertOk();

        $payment->refresh();
        $this->assertSame('cancelado', $order->fresh()->status);
        $this->assertSame('failed', $payment->refund_status);
        $this->assertSame(1, $payment->refund_attempts);

        $this->refundUnavailable = false;
        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame('refunded', $payment->fresh()->refund_status);
        $this->assertSame(1, $listing->fresh()->capacity);
    }

    public function test_seller_viewing_order_status_does_not_see_payment_actions(): void
    {
        $this->addToCart($this->buyer, $this->cosmetic());
        $order = $this->checkoutOrFail($this->buyer);

        $this->actingAs($this->seller)->get(route('checkout.success', $order))
            ->assertOk()->assertDontSee('PIX copia e cola')->assertDontSee('Já paguei');
        $this->actingAs($this->buyer)->get(route('checkout.success', $order))
            ->assertOk()->assertSee('PIX copia e cola')->assertSee('Já paguei');
    }

    private function checkoutOrFail(User $buyer): Order
    {
        $response = $this->checkout($buyer);
        $order = $this->lastOrderOf($buyer);

        $this->assertNotNull($order, 'Checkout deveria criar um pedido: '.json_encode(session('error')));
        $response->assertRedirect(route('checkout.success', $order));

        return $order->load('payment');
    }

    private function payViaWebhook(Order $order): void
    {
        $chargeId = $order->payment()->sole()->transaction_id;
        $this->setChargeStatus($chargeId, 'PAID');
        $this->postWebhook($this->completedEvent($chargeId))->assertOk();
        $this->assertSame('pago', $order->fresh()->status);
    }
}
