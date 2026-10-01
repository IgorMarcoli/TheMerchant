<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $otherBuyer;

    protected User $seller;

    protected Game $game;

    protected Category $cosmeticCategory;

    protected Category $serviceCategory;

    protected Listing $cosmeticListing;

    protected Listing $serviceListing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'name' => 'Vendedor Oficial',
            'email' => 'seller@example.test',
            'status' => 'active',
        ]);
        $this->seller->sellerProfile()->create([
            'status' => 'approved',
            'reputation_score' => 5.00,
            'total_reviews' => 0,
        ]);

        $this->buyer = User::factory()->create([
            'name' => 'Comprador Ativo',
            'email' => 'buyer@example.test',
            'status' => 'active',
        ]);

        $this->otherBuyer = User::factory()->create([
            'name' => 'Outro Comprador',
            'email' => 'other@example.test',
            'status' => 'active',
        ]);

        $this->game = Game::create([
            'name' => 'Valorant',
            'slug' => 'valorant',
            'is_active' => true,
        ]);

        $this->cosmeticCategory = Category::create([
            'game_id' => $this->game->id,
            'name' => 'Skins de Vandal',
            'slug' => 'skins-vandal',
            'type' => 'cosmetic',
            'is_active' => true,
        ]);

        $this->serviceCategory = Category::create([
            'game_id' => $this->game->id,
            'name' => 'Coaching e Mentoria',
            'slug' => 'coaching-mentoria',
            'type' => 'service',
            'is_active' => true,
        ]);

        $this->cosmeticListing = Listing::create([
            'seller_id' => $this->seller->id,
            'game_id' => $this->game->id,
            'category_id' => $this->cosmeticCategory->id,
            'title' => 'Vandal Sublime 2.0',
            'slug' => 'vandal-sublime-2-0',
            'description' => 'Skin de alta qualidade para Vandal.',
            'price' => 150.00,
            'status' => 'publicado',
        ]);

        $this->serviceListing = Listing::create([
            'seller_id' => $this->seller->id,
            'game_id' => $this->game->id,
            'category_id' => $this->serviceCategory->id,
            'title' => 'Coaching Individual de Mira e Posicionamento',
            'slug' => 'coaching-individual',
            'description' => 'Sessão de coaching individual de 60 minutos.',
            'price' => 80.00,
            'status' => 'publicado',
        ]);
    }

    public function test_visitor_is_redirected_to_login_when_accessing_orders(): void
    {
        $this->get(route('orders.index'))->assertRedirect(route('login'));

        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'buyer_id' => $this->buyer->id,
            'total_amount' => 150.00,
            'status' => 'pendente',
        ]);

        $this->get(route('orders.show', $order))->assertRedirect(route('login'));
    }

    public function test_buyer_can_view_orders_index_and_sees_only_own_orders(): void
    {
        $buyerOrder = Order::create([
            'order_number' => 'ORD-BUYER-100',
            'buyer_id' => $this->buyer->id,
            'total_amount' => 150.00,
            'status' => 'pago',
        ]);

        OrderItem::create([
            'order_id' => $buyerOrder->id,
            'listing_id' => $this->cosmeticListing->id,
            'seller_id' => $this->seller->id,
            'unit_price' => 150.00,
            'quantity' => 1,
            'delivery_status' => 'em_entrega',
        ]);

        $otherOrder = Order::create([
            'order_number' => 'ORD-OTHER-200',
            'buyer_id' => $this->otherBuyer->id,
            'total_amount' => 300.00,
            'status' => 'concluido',
        ]);

        OrderItem::create([
            'order_id' => $otherOrder->id,
            'listing_id' => $this->cosmeticListing->id,
            'seller_id' => $this->seller->id,
            'unit_price' => 300.00,
            'quantity' => 2,
            'delivery_status' => 'entregue',
        ]);

        $response = $this->actingAs($this->buyer)->get(route('orders.index'));

        $response->assertOk();
        $response->assertSee('ORD-BUYER-100');
        $response->assertDontSee('ORD-OTHER-200');
        $response->assertSee('Vandal Sublime 2.0');
    }

    public function test_orders_index_is_chronologically_paginated(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $order = Order::create([
                'order_number' => sprintf('ORD-PAG-%03d', $i),
                'buyer_id' => $this->buyer->id,
                'total_amount' => 50.00 + $i,
                'status' => 'pago',
            ]);

            DB::table('orders')
                ->where('id', $order->id)
                ->update(['created_at' => now()->subMinutes(20 - $i)]);
        }

        $response = $this->actingAs($this->buyer)->get(route('orders.index'));

        $response->assertOk();
        // Latest first: ORD-PAG-012 should be on page 1
        $response->assertSee('ORD-PAG-012');
        // Oldest (ORD-PAG-001) should be on page 2 (paginate 10)
        $response->assertDontSee('ORD-PAG-001');

        $page2 = $this->actingAs($this->buyer)->get(route('orders.index', ['page' => 2]));
        $page2->assertOk();
        $page2->assertSee('ORD-PAG-001');
    }

    public function test_buyer_can_view_details_of_own_order_with_timeline_and_payment(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-DETAIL-001',
            'buyer_id' => $this->buyer->id,
            'total_amount' => 230.00,
            'status' => 'em_andamento',
            'notes' => 'Trade URL: https://steamcommunity.com/tradeoffer/new/?partner=12345',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'gateway' => 'mercadopago',
            'transaction_id' => 'mp_trans_998877',
            'status' => 'approved',
            'amount' => 230.00,
            'idempotency_key' => 'idemp_key_998877',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'listing_id' => $this->cosmeticListing->id,
            'seller_id' => $this->seller->id,
            'unit_price' => 150.00,
            'quantity' => 1,
            'delivery_status' => 'em_entrega',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'listing_id' => $this->serviceListing->id,
            'seller_id' => $this->seller->id,
            'unit_price' => 80.00,
            'quantity' => 1,
            'delivery_status' => 'aguardando_pagamento',
        ]);

        $response = $this->actingAs($this->buyer)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('ORD-DETAIL-001');
        $response->assertSee('R$ 230,00');
        $response->assertSee('mp_trans_998877');
        $response->assertSee('MERCADOPAGO');
        $response->assertSee('Linha do Tempo do Pedido');
        $response->assertSee('Em Andamento');
        $response->assertSee('Trade URL: https://steamcommunity.com/tradeoffer/new/?partner=12345');
        $response->assertSee('Vandal Sublime 2.0');
        $response->assertSee('Coaching Individual de Mira e Posicionamento');
        $response->assertSee('Cosmético Digital');
        $response->assertSee('Serviço');
        $response->assertSee('Vendedor Oficial');
    }

    public function test_buyer_cannot_view_details_of_another_buyers_order(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SECRET-001',
            'buyer_id' => $this->buyer->id,
            'total_amount' => 150.00,
            'status' => 'pago',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'listing_id' => $this->cosmeticListing->id,
            'seller_id' => $this->seller->id,
            'unit_price' => 150.00,
            'quantity' => 1,
            'delivery_status' => 'em_entrega',
        ]);

        // Attempt by another buyer who is not a participant
        $this->actingAs($this->otherBuyer)
            ->get(route('orders.show', $order))
            ->assertForbidden();
    }

    public function test_order_timeline_distinguishes_order_status_from_individual_item_delivery(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-DISTINCT-001',
            'buyer_id' => $this->buyer->id,
            'total_amount' => 150.00,
            'status' => 'pago', // Order is 'pago'
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'listing_id' => $this->cosmeticListing->id,
            'seller_id' => $this->seller->id,
            'unit_price' => 150.00,
            'quantity' => 1,
            'delivery_status' => 'em_entrega', // Item is 'em_entrega'
        ]);

        $response = $this->actingAs($this->buyer)->get(route('orders.show', $order));

        $response->assertOk();
        // Order status
        $response->assertSee('Status do Pedido: Pago');
        // Item delivery status
        $response->assertSee('Entrega: Em entrega');
    }

    public function test_buyer_can_review_seller_only_after_item_is_delivered(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-REVIEW-001',
            'buyer_id' => $this->buyer->id,
            'total_amount' => 150.00,
            'status' => 'em_andamento',
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'listing_id' => $this->cosmeticListing->id,
            'seller_id' => $this->seller->id,
            'unit_price' => 150.00,
            'quantity' => 1,
            'delivery_status' => 'em_entrega',
        ]);

        // Before delivery: review should fail (400)
        $this->actingAs($this->buyer)
            ->post(route('orders.review.store', [$order, $item]), [
                'rating' => 5,
                'comment' => 'Tentativa antecipada de avaliação',
            ])
            ->assertStatus(400);

        $this->assertDatabaseMissing('reviews', [
            'order_item_id' => $item->id,
        ]);

        // Mark as delivered
        $item->update([
            'delivery_status' => 'entregue',
            'delivered_at' => now(),
        ]);

        // After delivery: review succeeds
        $this->actingAs($this->buyer)
            ->post(route('orders.review.store', [$order, $item]), [
                'rating' => 5,
                'comment' => 'Vendedor super rápido e confiável!',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'order_item_id' => $item->id,
            'rating' => 5,
            'comment' => 'Vendedor super rápido e confiável!',
        ]);

        // Seller reputation is updated
        $this->assertEquals(5.00, $this->seller->sellerProfile->fresh()->reputation_score);
        $this->assertEquals(1, $this->seller->sellerProfile->fresh()->total_reviews);
    }

    public function test_suspended_buyer_cannot_access_orders(): void
    {
        $this->buyer->update(['status' => 'suspended']);

        $this->actingAs($this->buyer)
            ->get(route('orders.index'))
            ->assertForbidden();
    }
}
