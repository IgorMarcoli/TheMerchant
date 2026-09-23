<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SellerPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_cannot_grant_administration_or_selling(): void
    {
        $this->post(route('register'), [
            'name' => 'Conta comum', 'email' => 'account@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'is_admin' => true, 'role' => 'admin', 'seller_status' => 'approved',
        ])->assertRedirect(route('home'));
        $user = User::firstOrFail();
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isSeller());
        $this->assertNull($user->sellerProfile);
    }

    public function test_application_is_pending_and_repeating_it_cannot_bypass_suspension(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('seller.application'))->assertOk();
        $this->post(route('seller.application.store'), ['bio' => 'Vendo itens digitais.', 'status' => 'approved'])
            ->assertRedirect(route('seller.application'));
        $this->assertDatabaseHas('seller_profiles', ['user_id' => $user->id, 'status' => 'pending']);
        $this->get(route('seller.anuncios.create'))->assertForbidden();
        $user->sellerProfile()->update(['status' => 'suspended']);
        $this->post(route('seller.application.store'), ['bio' => 'Uma nova solicitação.'])->assertRedirect();
        $this->assertDatabaseCount('seller_profiles', 1);
        $this->assertDatabaseHas('seller_profiles', ['status' => 'suspended']);
    }

    public function test_profile_update_cannot_approve_sales_or_grant_admin(): void
    {
        $user = User::factory()->create();
        $user->sellerProfile()->create(['status' => 'pending']);
        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name, 'email' => $user->email, 'bio' => 'Minha apresentação atualizada.',
            'is_admin' => true, 'seller_status' => 'approved', 'status' => 'approved',
        ])->assertRedirect(route('profile.edit'));
        $this->assertFalse($user->fresh()->isAdmin());
        $this->assertDatabaseHas('seller_profiles', [
            'user_id' => $user->id, 'status' => 'pending', 'bio' => 'Minha apresentação atualizada.',
        ]);
    }

    public function test_suspension_after_adding_to_cart_blocks_checkout_before_gateway(): void
    {
        $seller = User::factory()->seller()->create();
        $listing = $this->listing($seller);
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('cart.add', $listing))->assertRedirect(route('cart.index'));
        $seller->sellerProfile()->update(['status' => 'suspended']);
        $gateway = $this->createMock(PaymentGatewayService::class);
        $gateway->expects($this->never())->method('createPaymentPreference');
        try {
            (new CheckoutService($gateway))->checkout($buyer);
            $this->fail('Checkout must reject a suspended seller.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('não está mais disponível', $exception->getMessage());
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_only_admin_can_approve_sellers_and_admin_is_not_automatically_seller(): void
    {
        $user = User::factory()->create();
        $user->sellerProfile()->create(['status' => 'pending']);
        $data = ['status' => 'active', 'is_admin' => false, 'seller_status' => 'approved'];
        $this->actingAs($user)->put(route('admin.usuarios.update', $user), $data)->assertForbidden();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.usuarios.index'))->assertOk()->assertSee($user->email);
        $this->get(route('seller.anuncios.create'))->assertForbidden();
        $this->put(route('admin.usuarios.update', $user), $data)->assertRedirect();
        $this->assertTrue($user->fresh()->isSeller());
        $this->actingAs($user)->get(route('seller.anuncios.create'))->assertOk();
    }

    public function test_seller_can_buy_from_another_seller_but_not_from_themself(): void
    {
        $seller = User::factory()->seller()->create();
        $listing = $this->listing($seller);
        $buyerAndSeller = User::factory()->seller()->create();
        $this->actingAs($buyerAndSeller)->post(route('cart.add', $listing))->assertRedirect(route('cart.index'));
        $this->assertDatabaseCount('cart_items', 1);
        $this->actingAs($seller)->post(route('cart.add', $listing))->assertSessionHas('error');
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_selling_suspension_blocks_new_sales_but_preserves_buying_and_delivery(): void
    {
        $seller = User::factory()->seller()->create();
        $listing = $this->listing($seller);
        $buyer = User::factory()->create();
        $order = Order::create(['order_number' => 'ORDER-1', 'buyer_id' => $buyer->id, 'total_amount' => 10, 'status' => 'pago']);
        $item = OrderItem::create(['order_id' => $order->id, 'listing_id' => $listing->id, 'seller_id' => $seller->id, 'unit_price' => 10, 'quantity' => 1, 'delivery_status' => 'em_entrega']);
        $seller->sellerProfile()->update(['status' => 'suspended']);
        $this->actingAs($seller)->get(route('seller.anuncios.create'))->assertForbidden();
        $this->patch(route('seller.anuncios.status', $listing))->assertForbidden();
        $this->get(route('cart.index'))->assertOk();
        $this->get(route('seller.sales.index'))->assertOk();
        $this->patch(route('seller.sales.deliver', $item))->assertRedirect();
        $this->assertDatabaseHas('order_items', ['id' => $item->id, 'delivery_status' => 'entregue']);
        $this->get(route('home'))->assertDontSee($listing->title);
        $this->get(route('listings.index'))->assertDontSee($listing->title);
        $this->get(route('listings.show', $listing->slug))->assertNotFound();
        $this->actingAs($buyer)->post(route('cart.add', $listing))->assertSessionHas('error');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_account_suspension_blocks_authenticated_actions(): void
    {
        $user = User::factory()->seller()->create(['status' => 'suspended']);
        $this->actingAs($user)->get(route('cart.index'))->assertForbidden();
        $this->get(route('seller.sales.index'))->assertForbidden();
        $this->post(route('seller.application.store'), ['bio' => 'Venda de itens digitais.'])->assertForbidden();
        $this->assertFalse($user->isSeller());
    }

    public function test_seller_cannot_edit_another_sellers_listing(): void
    {
        $listing = $this->listing(User::factory()->seller()->create());
        $this->actingAs(User::factory()->seller()->create())
            ->patch(route('seller.anuncios.status', $listing))->assertForbidden();
        $this->assertDatabaseHas('listings', ['id' => $listing->id, 'status' => 'publicado']);
    }

    public function test_migration_preserves_legacy_permissions_and_profiles(): void
    {
        $migration = require database_path('migrations/2026_09_23_000001_separate_selling_permissions_from_users.php');
        $migration->down();
        $adminId = DB::table('users')->insertGetId(['name' => 'Admin', 'email' => 'legacy-admin@example.test', 'password' => 'hash', 'role' => 'admin']);
        $sellerId = DB::table('users')->insertGetId(['name' => 'Seller', 'email' => 'legacy-seller@example.test', 'password' => 'hash', 'role' => 'seller']);
        $buyerId = DB::table('users')->insertGetId(['name' => 'Buyer', 'email' => 'legacy-buyer@example.test', 'password' => 'hash', 'role' => 'buyer']);
        $migration->up();
        $this->assertFalse(Schema::hasColumn('users', 'role'));
        $this->assertTrue(User::findOrFail($adminId)->isAdmin());
        $this->assertFalse(User::findOrFail($adminId)->isSeller());
        $this->assertTrue(User::findOrFail($sellerId)->isSeller());
        $this->assertFalse(User::findOrFail($buyerId)->isSeller());
    }

    private function listing(User $seller): Listing
    {
        $game = Game::create(['name' => 'Game', 'slug' => 'game']);
        $category = Category::create(['name' => 'Skins', 'slug' => 'skins', 'game_id' => $game->id]);

        return Listing::create([
            'seller_id' => $seller->id, 'game_id' => $game->id, 'category_id' => $category->id,
            'title' => 'Anúncio de teste restrito', 'slug' => 'test-listing', 'description' => 'Descrição do item de teste.',
            'price' => 10, 'status' => 'publicado',
        ]);
    }
}
