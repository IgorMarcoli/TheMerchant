<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemDelivery;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Entrega digital (#18) consolidada na #24: idempotência, autorização,
 * pagamento obrigatório, sessões de coaching e conclusão do pedido.
 */
class SaleDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->seller()->create();
        $this->buyer = User::factory()->create();
    }

    public function test_cosmetic_delivery_is_idempotent_and_counts_the_sale_once(): void
    {
        $item = $this->sale($this->cosmetic());

        $this->actingAs($this->seller)->patch(route('seller.sales.deliver', $item))
            ->assertRedirect()->assertSessionHas('success', 'Entrega concluída com sucesso!');
        $this->patch(route('seller.sales.deliver', $item))
            ->assertRedirect()->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'já estava entregue'));

        $item->refresh();
        $this->assertSame('entregue', $item->delivery_status);
        $this->assertNotNull($item->delivered_at);
        $this->assertSame(1, $item->deliveries()->count());
        $this->assertSame('concluido', $item->order->status);
        $this->assertSame(1, $this->seller->sellerProfile()->value('total_sales'));
    }

    public function test_only_the_item_seller_can_deliver(): void
    {
        $item = $this->sale($this->cosmetic());
        $otherSeller = User::factory()->seller()->create();
        $admin = User::factory()->admin()->create();

        foreach ([$otherSeller, $this->buyer, $admin] as $user) {
            $this->actingAs($user)->patch(route('seller.sales.deliver', $item))->assertForbidden();
        }

        $this->assertSame('em_entrega', $item->fresh()->delivery_status);
        $this->assertDatabaseCount('order_item_deliveries', 0);
        $this->assertSame(0, $this->seller->sellerProfile()->value('total_sales'));
    }

    public function test_guest_and_suspended_account_cannot_deliver(): void
    {
        $item = $this->sale($this->cosmetic());

        $this->patch(route('seller.sales.deliver', $item))->assertRedirect(route('login'));

        $this->seller->update(['status' => 'suspended']);
        $this->actingAs($this->seller)->patch(route('seller.sales.deliver', $item))->assertForbidden();

        $this->assertDatabaseCount('order_item_deliveries', 0);
    }

    public function test_unpaid_or_cancelled_order_cannot_be_delivered(): void
    {
        foreach (['pendente', 'cancelado'] as $status) {
            $item = $this->sale($this->cosmetic(), orderStatus: $status);

            $this->actingAs($this->seller)->patch(route('seller.sales.deliver', $item))
                ->assertSessionHasErrors('delivery');

            $this->assertSame('em_entrega', $item->fresh()->delivery_status);
            $this->assertSame($status, $item->order->fresh()->status);
        }

        $this->assertDatabaseCount('order_item_deliveries', 0);
    }

    public function test_coaching_sessions_are_recorded_in_order_without_exceeding_the_contract(): void
    {
        $item = $this->sale($this->coaching(), quantity: 3);
        $this->actingAs($this->seller);

        $this->patch(route('seller.sales.deliver', $item))->assertSessionHasErrors('session_number');
        $this->patch(route('seller.sales.deliver', $item), ['session_number' => 2])->assertSessionHasErrors('session_number');
        $this->patch(route('seller.sales.deliver', $item), ['session_number' => 4])->assertSessionHasErrors('session_number');
        $this->assertDatabaseCount('order_item_deliveries', 0);

        $this->patch(route('seller.sales.deliver', $item), ['session_number' => 1])
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'Sessão registrada'));
        $this->patch(route('seller.sales.deliver', $item), ['session_number' => 1])
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'já havia sido registrada'));
        $this->assertSame('em_entrega', $item->fresh()->delivery_status);
        $this->assertSame('pago', $item->order->fresh()->status);

        $this->patch(route('seller.sales.deliver', $item), ['session_number' => 2])->assertSessionHasNoErrors();
        $this->patch(route('seller.sales.deliver', $item), ['session_number' => 3])
            ->assertSessionHas('success', 'Entrega concluída com sucesso!');

        $this->assertSame([1, 2, 3], $item->deliveries()->orderBy('delivery_number')->pluck('delivery_number')->all());
        $this->assertSame('entregue', $item->fresh()->delivery_status);
        $this->assertSame('concluido', $item->order->fresh()->status);
        $this->assertSame(1, $this->seller->sellerProfile()->value('total_sales'));
    }

    public function test_order_is_concluded_only_after_every_seller_delivers(): void
    {
        $otherSeller = User::factory()->seller()->create();
        $mine = $this->sale($this->cosmetic());
        $theirs = OrderItem::create([
            'order_id' => $mine->order_id,
            'listing_id' => $this->cosmetic($otherSeller)->id,
            'seller_id' => $otherSeller->id,
            'unit_price' => 50,
            'quantity' => 1,
            'delivery_status' => 'em_entrega',
        ]);

        $this->actingAs($this->seller)->patch(route('seller.sales.deliver', $mine))->assertSessionHasNoErrors();
        $this->assertSame('pago', $mine->order->fresh()->status);

        $this->actingAs($otherSeller)->patch(route('seller.sales.deliver', $theirs))->assertSessionHasNoErrors();
        $this->assertSame('concluido', $mine->order->fresh()->status);
        $this->assertSame(1, $this->seller->sellerProfile()->value('total_sales'));
        $this->assertSame(1, $otherSeller->sellerProfile()->value('total_sales'));
    }

    public function test_database_rejects_duplicate_delivery_number(): void
    {
        $item = $this->sale($this->coaching(), quantity: 2);
        $row = ['order_item_id' => $item->id, 'delivery_number' => 1, 'recorded_by' => $this->seller->id, 'delivered_at' => now()];
        OrderItemDelivery::create($row);

        $this->assertThrows(fn () => OrderItemDelivery::create($row), UniqueConstraintViolationException::class);
    }

    private function sale(Listing $listing, int $quantity = 1, string $orderStatus = 'pago'): OrderItem
    {
        $order = Order::create([
            'order_number' => 'ORD-'.Str::upper(Str::random(8)),
            'buyer_id' => $this->buyer->id,
            'total_amount' => $listing->price * $quantity,
            'status' => $orderStatus,
        ]);

        return OrderItem::create([
            'order_id' => $order->id,
            'listing_id' => $listing->id,
            'seller_id' => $listing->seller_id,
            'unit_price' => $listing->price,
            'quantity' => $quantity,
            'delivery_status' => 'em_entrega',
        ]);
    }

    private function cosmetic(?User $seller = null): Listing
    {
        return $this->listing($seller ?? $this->seller, 'cosmetic');
    }

    private function coaching(): Listing
    {
        return $this->listing($this->seller, 'service', [
            'capacity' => 5,
            'session_duration_minutes' => 60,
            'timezone' => 'America/Sao_Paulo',
        ]);
    }

    private function listing(User $seller, string $type, array $extra = []): Listing
    {
        $game = Game::firstOrCreate(['slug' => 'cs2'], ['name' => 'Counter-Strike 2', 'is_active' => true]);
        $category = Category::firstOrCreate(
            ['slug' => $type],
            ['game_id' => $game->id, 'name' => ucfirst($type), 'type' => $type, 'is_active' => true]
        );
        $title = ucfirst($type).' '.Str::random(6);

        return Listing::create([
            'seller_id' => $seller->id,
            'game_id' => $game->id,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => 'Oferta de teste da entrega.',
            'price' => 50,
            'status' => 'vendido',
        ] + $extra);
    }
}
