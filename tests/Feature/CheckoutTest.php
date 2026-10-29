<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private User $buyer;

    private Game $game;

    private Category $cosmeticCategory;

    private Category $serviceCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->seller()->create(['name' => 'Pro Seller']);
        $this->buyer = User::factory()->create(['name' => 'Buyer Gamer']);

        $this->game = Game::create([
            'name' => 'CS2',
            'slug' => 'cs2',
            'active' => true,
        ]);

        $this->cosmeticCategory = Category::create([
            'game_id' => $this->game->id,
            'name' => 'Skins',
            'slug' => 'skins',
            'type' => 'cosmetic',
            'is_active' => true,
        ]);

        $this->serviceCategory = Category::create([
            'game_id' => $this->game->id,
            'name' => 'Coaching',
            'slug' => 'coaching',
            'type' => 'service',
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_add_item_to_cart_and_prevents_duplicates(): void
    {
        $listing = $this->createCosmeticListing('AK-47 Redline FT', 150.00);

        $response = $this->actingAs($this->buyer)->post(route('cart.add', $listing));

        $response->assertRedirect(route('cart.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cart_items', [
            'listing_id' => $listing->id,
            'quantity' => 1,
            'unit_price' => 150.00,
        ]);

        // Tentativa de adicionar o mesmo anúncio novamente mantém unicidade
        $duplicateResponse = $this->actingAs($this->buyer)
            ->from(route('listings.show', $listing->slug))
            ->post(route('cart.add', $listing));

        $duplicateResponse->assertRedirect(route('listings.show', $listing->slug))
            ->assertSessionHas('info');

        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_empty_cart_displays_empty_state_with_catalog_link(): void
    {
        $response = $this->actingAs($this->buyer)->get(route('cart.index'));

        $response->assertOk()
            ->assertSee('Seu carrinho está vazio')
            ->assertSee('Explorar anúncios')
            ->assertSee(route('listings.index'), false);
    }

    public function test_cart_displays_items_seller_subtotals_and_exact_cent_total(): void
    {
        $cosmetic = $this->createCosmeticListing('Karambit Doppler', 40.03);
        $service = $this->createServiceListing('Coaching Tático CS2', 19.99, capacity: 5, duration: 60);

        $this->actingAs($this->buyer)->post(route('cart.add', $cosmetic));
        $this->actingAs($this->buyer)->post(route('cart.add', $service));

        $serviceItem = CartItem::where('listing_id', $service->id)->firstOrFail();

        $this->actingAs($this->buyer)
            ->from(route('cart.index'))
            ->patch(route('cart.update', $serviceItem), ['quantity' => 3])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success');

        $response = $this->actingAs($this->buyer)->get(route('cart.index'));

        // 40.03 + (19.99 * 3 = 59.97) = 100.00
        $response->assertOk()
            ->assertSee('Karambit Doppler')
            ->assertSee('Coaching Tático CS2')
            ->assertSee('Pro Seller')
            ->assertSee('60 min cada')
            ->assertSee('(item único)')
            ->assertSee('R$ 40,03')
            ->assertSee('R$ 59,97')
            ->assertSee('R$ 100,00')
            ->assertSee('Esvaziar carrinho')
            ->assertSee('Ir para o checkout');
    }

    public function test_cart_rejects_quantity_update_for_cosmetic_or_above_service_capacity_or_invalid_values(): void
    {
        $cosmetic = $this->createCosmeticListing('AWP Asiimov', 200.00);
        $service = $this->createServiceListing('Mentoria Individual', 50.00, capacity: 3, duration: 45);

        $this->actingAs($this->buyer)->post(route('cart.add', $cosmetic));
        $this->actingAs($this->buyer)->post(route('cart.add', $service));

        $cosmeticItem = CartItem::where('listing_id', $cosmetic->id)->firstOrFail();
        $serviceItem = CartItem::where('listing_id', $service->id)->firstOrFail();

        // Cosmético não pode ter quantidade alterada
        $this->actingAs($this->buyer)
            ->from(route('cart.index'))
            ->patch(route('cart.update', $cosmeticItem), ['quantity' => 2])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertSame(1, $cosmeticItem->fresh()->quantity);

        // Serviço acima da capacidade disponível é bloqueado
        $this->actingAs($this->buyer)
            ->from(route('cart.index'))
            ->patch(route('cart.update', $serviceItem), ['quantity' => 4])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertSame(1, $serviceItem->fresh()->quantity);

        // Quantidade menor que 1 ou não inteira falha no Form Request
        $this->actingAs($this->buyer)
            ->from(route('cart.index'))
            ->patch(route('cart.update', $serviceItem), ['quantity' => 0])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('quantity');

        $this->actingAs($this->buyer)
            ->from(route('cart.index'))
            ->patch(route('cart.update', $serviceItem), ['quantity' => '1.5'])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('quantity');
    }

    public function test_buyer_can_remove_single_item_and_clear_entire_cart(): void
    {
        $first = $this->createCosmeticListing('M4A4 Howl', 500.00);
        $second = $this->createCosmeticListing('Desert Eagle Blaze', 120.00);

        $this->actingAs($this->buyer)->post(route('cart.add', $first));
        $this->actingAs($this->buyer)->post(route('cart.add', $second));

        $firstItem = CartItem::where('listing_id', $first->id)->firstOrFail();

        // Remove um item
        $this->actingAs($this->buyer)
            ->from(route('cart.index'))
            ->delete(route('cart.remove', $firstItem))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('cart_items', ['id' => $firstItem->id]);
        $this->assertDatabaseCount('cart_items', 1);

        // Limpa o carrinho inteiro
        $this->actingAs($this->buyer)
            ->from(route('cart.index'))
            ->delete(route('cart.clear'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('info');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_buyer_cannot_update_or_remove_another_users_cart_item(): void
    {
        $service = $this->createServiceListing('Coaching Valorant/CS2', 70.00, capacity: 4, duration: 60);
        $otherBuyer = User::factory()->create();

        $this->actingAs($this->buyer)->post(route('cart.add', $service));
        $item = CartItem::firstOrFail();

        $this->actingAs($otherBuyer)
            ->patch(route('cart.update', $item), ['quantity' => 2])
            ->assertForbidden();

        $this->actingAs($otherBuyer)
            ->delete(route('cart.remove', $item))
            ->assertForbidden();

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 1,
        ]);
    }

    public function test_cart_displays_alerts_and_disables_checkout_when_item_becomes_unavailable_or_exceeds_capacity(): void
    {
        $service = $this->createServiceListing('Coaching Avançado', 90.00, capacity: 4, duration: 60);

        $this->actingAs($this->buyer)->post(route('cart.add', $service));
        $item = CartItem::firstOrFail();

        $this->actingAs($this->buyer)
            ->patch(route('cart.update', $item), ['quantity' => 3]);

        // Capacidade cai abaixo da quantidade selecionada no carrinho
        $service->update(['capacity' => 2]);

        $this->actingAs($this->buyer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Quantidade indisponível no momento')
            ->assertSee('Ajuste os itens indisponíveis');

        // Anúncio é pausado pelo vendedor
        $service->update(['status' => 'pausado']);

        $this->actingAs($this->buyer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Este anúncio não está mais disponível para compra.')
            ->assertSee('Ajuste os itens indisponíveis');
    }

    private function createCosmeticListing(string $title, float $price): Listing
    {
        return Listing::create([
            'seller_id' => $this->seller->id,
            'game_id' => $this->game->id,
            'category_id' => $this->cosmeticCategory->id,
            'title' => $title,
            'slug' => str()->slug($title).'-'.str()->random(6),
            'description' => 'Descrição detalhada do cosmético.',
            'price' => $price,
            'status' => 'publicado',
            'capacity' => 1,
        ]);
    }

    private function createServiceListing(string $title, float $price, int $capacity = 5, int $duration = 60): Listing
    {
        return Listing::create([
            'seller_id' => $this->seller->id,
            'game_id' => $this->game->id,
            'category_id' => $this->serviceCategory->id,
            'title' => $title,
            'slug' => str()->slug($title).'-'.str()->random(6),
            'description' => 'Descrição detalhada do coaching.',
            'price' => $price,
            'status' => 'publicado',
            'capacity' => $capacity,
            'session_duration_minutes' => $duration,
            'delivery_conditions' => 'Combinar pelo chat após pagamento.',
            'timezone' => 'America/Sao_Paulo',
        ]);
    }
}
