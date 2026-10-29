<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Game;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerListingTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;
    private Game $game;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['filesystems.default' => 'local']);
        $this->seller = User::factory()->seller()->create();
        $this->game = Game::create(['name' => 'Jogo', 'slug' => 'listing-game', 'active' => true]);
        $this->category = Category::create(['name' => 'Skins', 'slug' => 'listing-category', 'game_id' => $this->game->id, 'type' => 'cosmetic']);
    }

    public function test_seller_creates_gallery_with_six_public_images_and_selected_cover(): void
    {
        $data = $this->data();
        $data['images'] = array_map(fn ($i) => $this->photo('photo'.$i.'.png'), range(0, 5));
        $data['primary_image'] = 'new:2';
        $data['seller_id'] = User::factory()->create()->id;
        $this->actingAs($this->seller)->post(route('seller.anuncios.store'), $data)->assertRedirect(route('seller.anuncios.index'));
        $listing = Listing::with('images')->firstOrFail();
        $this->assertSame($this->seller->id, $listing->seller_id);
        $this->assertCount(6, $listing->images);
        $this->assertSame([0, 1, 2, 3, 4, 5], $listing->images->pluck('display_order')->all());
        $this->assertSame(1, $listing->images->where('is_primary', true)->count());
        $this->assertTrue($listing->images[2]->is_primary);
        foreach ($listing->images as $image) {
            $this->assertSame('public', $image->disk);
            Storage::disk('public')->assertExists($image->image_path);
            $this->assertSame('public', Storage::disk('public')->getVisibility($image->image_path));
            $this->assertSame(Storage::disk('public')->url($image->image_path), $image->url);
        }
        $this->get(route('seller.anuncios.index'))->assertOk()->assertSee('Editar')->assertSee($listing->images[2]->url, false);
    }

    public function test_store_rejects_missing_excessive_oversized_and_invalid_images(): void
    {
        $forged = UploadedFile::fake()->createWithContent('photo.png', '<?php echo "not an image";');
        $cases = [
            [],
            array_map(fn ($i) => $this->photo('photo'.$i.'.png'), range(0, 6)),
            [UploadedFile::fake()->create('photo.png', 4097, 'image/png')],
            [UploadedFile::fake()->createWithContent('photo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')],
            [new UploadedFile($forged->getPathname(), 'photo.png', 'image/png', null, true)],
        ];
        foreach ($cases as $index => $images) {
            $response = $this->actingAs($this->seller)->post(route('seller.anuncios.store'), array_replace($this->data(), ['images' => $images]));
            $response->assertStatus(302);
            $this->assertTrue(session()->has('errors'), 'Invalid image case '.$index.' must fail validation.');
            $this->assertDatabaseCount('listings', 0);
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_offer_rejects_invalid_price_and_incompatible_or_inactive_catalog(): void
    {
        foreach (['0.99', '1.001', '1000000'] as $price) {
            $this->actingAs($this->seller)->post(route('seller.anuncios.store'), array_replace($this->data(), ['price' => $price]))
                ->assertSessionHasErrors('price');
        }
        $otherGame = Game::create(['name' => 'Outro jogo', 'slug' => 'other']);
        $otherCategory = Category::create(['name' => 'Outra', 'slug' => 'other', 'game_id' => $otherGame->id]);
        $this->post(route('seller.anuncios.store'), array_replace($this->data(), ['category_id' => $otherCategory->id]))->assertSessionHasErrors('category_id');
        $this->category->update(['is_active' => false]);
        $this->post(route('seller.anuncios.store'), $this->data())->assertSessionHasErrors('category_id');
        $this->game->update(['active' => false]);
        $this->post(route('seller.anuncios.store'), $this->data())->assertSessionHasErrors('game_id');
        $this->assertDatabaseCount('listings', 0);
    }

    public function test_global_category_and_long_title_can_be_published(): void
    {
        $this->category->update(['game_id' => null]);
        $data = array_replace($this->data(), ['title' => str_repeat('a', 150)]);
        $this->actingAs($this->seller)->post(route('seller.anuncios.store'), $data)->assertSessionHasNoErrors();
        $this->assertLessThanOrEqual(180, strlen(Listing::firstOrFail()->slug));
    }

    public function test_coaching_requires_integer_capacity_duration_and_timezone(): void
    {
        $this->category->update(['type' => 'service']);
        $data = array_replace($this->data(), ['capacity' => 5, 'session_duration_minutes' => 60, 'timezone' => 'America/Sao_Paulo']);
        foreach ([['capacity' => 0], ['capacity' => 1.5], ['session_duration_minutes' => 0], ['timezone' => 'Invalid/Zone']] as $invalid) {
            $this->actingAs($this->seller)->post(route('seller.anuncios.store'), array_replace($data, $invalid))->assertSessionHasErrors(array_keys($invalid));
        }
        $this->post(route('seller.anuncios.store'), $data)->assertSessionHasNoErrors();
        $listing = Listing::firstOrFail();
        $this->assertSame(5, $listing->capacity);
        $this->assertSame(60, $listing->session_duration_minutes);
        $this->get(route('listings.show', $listing->slug))->assertOk()->assertSee('60 minutos')->assertSee('Horário a combinar pelo chat');
    }

    public function test_cosmetic_cannot_have_multiple_units_or_session_details(): void
    {
        $this->actingAs($this->seller)->post(route('seller.anuncios.store'), array_replace($this->data(), ['capacity' => 2]))->assertSessionHasErrors('capacity');
        $this->post(route('seller.anuncios.store'), array_replace($this->data(), ['session_duration_minutes' => 60]))->assertSessionHasErrors('session_duration_minutes');
        $this->assertDatabaseCount('listings', 0);
    }

    public function test_owner_can_edit_offer_keep_one_image_and_add_another_as_cover(): void
    {
        $listing = $this->listing();
        $images = $listing->images;
        $this->actingAs($this->seller)->get(route('seller.anuncios.edit', $listing))->assertOk()->assertSee('Galeria de fotos');
        $data = array_replace($this->data(), [
            'title' => 'Título atualizado', 'price' => '25.50', 'images' => [$this->photo('new.png')],
            'retained_images' => [$images[1]->id], 'primary_image' => 'new:0',
        ]);
        $this->put(route('seller.anuncios.update', $listing), $data)->assertRedirect(route('seller.anuncios.index'));
        $listing->refresh();
        $this->assertSame('Título atualizado', $listing->title);
        $this->assertSame('25.50', $listing->price);
        $this->assertCount(2, $listing->images);
        $this->assertDatabaseMissing('listing_images', ['id' => $images[0]->id]);
        $this->assertSame(1, $listing->images->where('is_primary', true)->count());
        $this->assertSame(1, $listing->primaryImage->display_order);
    }

    public function test_gallery_cannot_be_empty_exceed_six_or_reuse_another_listings_image(): void
    {
        $listing = $this->listing();
        $other = $this->listing();
        $data = $this->data();
        unset($data['images']);
        $this->actingAs($this->seller)->put(route('seller.anuncios.update', $listing), $data)->assertSessionHasErrors('images');
        $this->put(route('seller.anuncios.update', $listing), array_replace($data, ['retained_images' => [$other->images->first()->id]]))
            ->assertSessionHasErrors('retained_images.0');
        $this->put(route('seller.anuncios.update', $listing), array_replace($data, [
            'retained_images' => $listing->images->modelKeys(), 'primary_image' => 'existing:'.$other->images->first()->id,
        ]))->assertSessionHasErrors('primary_image');
        $this->put(route('seller.anuncios.update', $listing), array_replace($data, [
            'retained_images' => $listing->images->modelKeys(),
            'images' => array_map(fn ($i) => $this->photo('new'.$i.'.png'), range(0, 4)),
        ]))->assertSessionHasErrors('images');
        $this->assertCount(2, $listing->fresh()->images);
    }

    public function test_owner_can_select_existing_cover_without_uploading_again(): void
    {
        $listing = $this->listing();
        $data = $this->data();
        unset($data['images']);
        $data['retained_images'] = $listing->images->modelKeys();
        $data['primary_image'] = 'existing:'.$listing->images[1]->id;
        $this->actingAs($this->seller)->put(route('seller.anuncios.update', $listing), $data)->assertSessionHasNoErrors();
        $this->assertSame($listing->images[1]->id, $listing->fresh()->primaryImage->id);
    }

    public function test_another_seller_is_forbidden_from_editing_updating_archiving_or_pausing(): void
    {
        $listing = $this->listing();
        $this->actingAs(User::factory()->seller()->create())->get(route('seller.anuncios.edit', $listing))->assertForbidden();
        $this->put(route('seller.anuncios.update', $listing), $this->data())->assertForbidden();
        $this->delete(route('seller.anuncios.destroy', $listing))->assertForbidden();
        $this->patch(route('seller.anuncios.status', $listing))->assertForbidden();
        $this->assertNull($listing->fresh()->archived_at);
    }

    public function test_buyer_pending_and_suspended_sellers_cannot_publish(): void
    {
        $this->post(route('seller.anuncios.store'), $this->data())->assertRedirect(route('login'));
        foreach ([User::factory()->create(), User::factory()->seller()->create(['status' => 'suspended'])] as $user) {
            $this->actingAs($user)->post(route('seller.anuncios.store'), $this->data())->assertForbidden();
        }
        $this->seller->sellerProfile()->update(['status' => 'pending']);
        $this->actingAs($this->seller)->post(route('seller.anuncios.store'), $this->data())->assertForbidden();
    }

    public function test_admin_can_manage_another_sellers_offer_without_own_seller_profile(): void
    {
        $listing = $this->listing();
        $data = $this->data();
        unset($data['images']);
        $data['retained_images'] = $listing->images->modelKeys();
        $this->actingAs(User::factory()->admin()->create())->get(route('seller.anuncios.edit', $listing))->assertOk();
        $this->put(route('seller.anuncios.update', $listing), $data)->assertSessionHasNoErrors();
        $this->assertSame($this->seller->id, $listing->fresh()->seller_id);
    }

    public function test_pause_and_reactivate_change_catalog_visibility(): void
    {
        $listing = $this->listing();
        $this->actingAs($this->seller)->patch(route('seller.anuncios.status', $listing))->assertRedirect();
        $this->assertSame('pausado', $listing->fresh()->status);
        $this->get(route('listings.index'))->assertDontSee($listing->title);
        $this->patch(route('seller.anuncios.status', $listing))->assertRedirect();
        $this->assertSame('publicado', $listing->fresh()->status);
        $this->get(route('listings.index'))->assertSee($listing->title);
    }

    public function test_blocked_and_sold_offers_cannot_be_edited_or_reactivated(): void
    {
        foreach (['bloqueado', 'vendido'] as $status) {
            $listing = $this->listing($status);
            $this->actingAs($this->seller)->get(route('seller.anuncios.edit', $listing))->assertForbidden();
            $this->put(route('seller.anuncios.update', $listing), $this->data())->assertForbidden();
            $this->patch(route('seller.anuncios.status', $listing))->assertForbidden();
            $this->assertSame($status, $listing->fresh()->status);
        }
    }

    public function test_paused_offer_cannot_reactivate_with_inactive_catalog(): void
    {
        $listing = $this->listing('pausado');
        $this->category->update(['is_active' => false]);
        $this->actingAs($this->seller)->patch(route('seller.anuncios.status', $listing))->assertSessionHasErrors('category_id');
        $this->assertSame('pausado', $listing->fresh()->status);
    }

    public function test_archiving_preserves_order_item_conversation_images_and_sale_history(): void
    {
        $listing = $this->listing('vendido');
        $buyer = User::factory()->create();
        $order = Order::create(['order_number' => 'LISTING-HISTORY', 'buyer_id' => $buyer->id, 'total_amount' => 10, 'status' => 'pago']);
        $item = $order->items()->create(['listing_id' => $listing->id, 'seller_id' => $this->seller->id, 'unit_price' => 10, 'quantity' => 1, 'delivery_status' => 'em_entrega', 'listing_title' => $listing->title]);
        Conversation::create(['listing_id' => $listing->id, 'buyer_id' => $buyer->id, 'seller_id' => $this->seller->id]);
        $this->actingAs($this->seller)->delete(route('seller.anuncios.destroy', $listing))->assertRedirect(route('seller.anuncios.index'));
        $this->assertNotNull($listing->fresh()->archived_at);
        $this->assertDatabaseHas('order_items', ['id' => $item->id, 'listing_id' => $listing->id]);
        $this->assertDatabaseCount('conversations', 1);
        $this->assertCount(2, $listing->fresh()->images);
        $this->get(route('seller.anuncios.index'))->assertDontSee($listing->title);
        $this->get(route('seller.sales.index'))->assertOk()->assertSee($listing->title);
        $this->get(route('listings.show', $listing->slug))->assertNotFound();
        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->assertSee($listing->title);
        $this->post(route('chat.start'), ['listing_id' => $listing->id])->assertRedirect();
    }

    public function test_archived_published_offer_blocks_new_cart_and_chat_but_keeps_existing_conversation(): void
    {
        $listing = $this->listing();
        $buyer = User::factory()->create();
        $conversation = Conversation::create(['listing_id' => $listing->id, 'buyer_id' => $buyer->id, 'seller_id' => $this->seller->id]);
        $this->actingAs($this->seller)->delete(route('seller.anuncios.destroy', $listing))->assertRedirect();
        $this->actingAs($buyer)->get(route('chat.show', $conversation))->assertRedirect();
        $this->get(route('chat.index', ['c' => $conversation->id]))->assertOk();
        $newBuyer = User::factory()->create();
        $this->actingAs($newBuyer)->post(route('cart.add', $listing))->assertSessionHas('error');
        $this->post(route('chat.start'), ['listing_id' => $listing->id])->assertSessionHasErrors('conversation');
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_existing_orders_prevent_category_and_capacity_changes(): void
    {
        $listing = $this->listing();
        $order = Order::create(['order_number' => 'IMMUTABLE-OFFER', 'buyer_id' => User::factory()->create()->id, 'total_amount' => 10, 'status' => 'pendente']);
        OrderItem::create(['order_id' => $order->id, 'listing_id' => $listing->id, 'seller_id' => $this->seller->id, 'unit_price' => 10]);
        $category = Category::create(['name' => 'Coaching', 'slug' => 'coaching', 'game_id' => $this->game->id, 'type' => 'service']);
        $data = array_replace($this->data(), ['category_id' => $category->id, 'capacity' => 10, 'session_duration_minutes' => 60, 'timezone' => 'America/Sao_Paulo', 'retained_images' => $listing->images->modelKeys()]);
        $this->actingAs($this->seller)->put(route('seller.anuncios.update', $listing), $data)->assertSessionHasErrors('category_id');
        $this->assertSame($this->category->id, $listing->fresh()->category_id);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_seller_index_only_shows_own_listings(): void
    {
        $mine = $this->listing();
        $other = $this->listing();
        $other->update(['seller_id' => User::factory()->seller()->create()->id, 'title' => 'Oferta de outro vendedor']);
        $this->actingAs($this->seller)->get(route('seller.anuncios.index'))->assertOk()->assertSee($mine->title)->assertDontSee($other->title);
    }

    private function data(): array
    {
        return ['game_id' => $this->game->id, 'category_id' => $this->category->id, 'title' => 'Uma skin especial',
            'description' => 'Uma descrição suficientemente detalhada do item.', 'price' => '10.00', 'status' => 'publicado',
            'delivery_conditions' => 'Entrega combinada diretamente pelo chat.', 'capacity' => 1, 'images' => [$this->photo()]];
    }

    private function listing(string $status = 'publicado'): Listing
    {
        $data = $this->data();
        unset($data['images']);
        $listing = $this->seller->listings()->create(array_replace($data, ['slug' => (string) \Illuminate\Support\Str::uuid(), 'status' => $status]));
        foreach ([0, 1] as $index) {
            $path = 'listings/'.$listing->id.'/photo'.$index.'.png';
            Storage::disk('public')->put($path, 'fixture');
            $listing->images()->create(['image_path' => $path, 'disk' => 'public', 'is_primary' => $index === 0, 'display_order' => $index]);
        }

        return $listing;
    }

    private function photo(string $name = 'skin.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l9sAAAAASUVORK5CYII='));
    }
}
