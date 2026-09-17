<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Game;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_item_to_cart(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);

        $game = Game::create(['name' => 'CS2', 'slug' => 'cs2', 'active' => true]);
        $cat = Category::create(['game_id' => $game->id, 'name' => 'Skins', 'slug' => 'skins']);

        $listing = Listing::create([
            'seller_id'   => $seller->id,
            'game_id'     => $game->id,
            'category_id' => $cat->id,
            'title'       => 'AK-47 Redline FT',
            'slug'        => 'ak-47-redline-ft',
            'description' => 'Test listing description',
            'price'       => 150.00,
            'status'      => 'publicado',
        ]);

        $response = $this->actingAs($buyer)->post(route('cart.add', $listing));

        $response->assertRedirect(route('cart.index'));
        $this->assertDatabaseHas('cart_items', [
            'listing_id' => $listing->id,
            'unit_price' => 150.00,
        ]);
    }
}
