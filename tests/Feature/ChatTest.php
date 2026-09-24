<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Game;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected User $buyer;

    protected Game $game;

    protected Category $category;

    protected Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create(['name' => 'Vendedor Pro', 'email' => 'seller@example.test']);
        $this->seller->sellerProfile()->create(['status' => 'approved']);

        $this->buyer = User::factory()->create(['name' => 'Comprador Gamer', 'email' => 'buyer@example.test']);

        $this->game = Game::create(['name' => 'Counter-Strike 2', 'slug' => 'cs2', 'is_active' => true]);
        $this->category = Category::create(['game_id' => $this->game->id, 'name' => 'Skins de Armas', 'slug' => 'skins']);

        $this->listing = Listing::create([
            'seller_id' => $this->seller->id,
            'game_id' => $this->game->id,
            'category_id' => $this->category->id,
            'title' => 'AK-47 Asiimov Pouco Usada',
            'slug' => 'ak-47-asiimov-pouco-usada',
            'description' => 'Skin de alta raridade e acabamento perfeito.',
            'price' => 250.00,
            'status' => 'publicado',
        ]);
    }

    public function test_visitor_is_redirected_to_login_when_accessing_chat(): void
    {
        $this->get(route('chat.index'))->assertRedirect(route('login'));

        $this->post(route('chat.start'), [
            'listing_id' => $this->listing->id,
        ])->assertRedirect(route('login'));
    }

    public function test_buyer_can_start_chat_from_listing(): void
    {
        $response = $this->actingAs($this->buyer)->post(route('chat.start'), [
            'listing_id' => $this->listing->id,
        ]);

        $conversation = Conversation::where('listing_id', $this->listing->id)
            ->where('buyer_id', $this->buyer->id)
            ->where('seller_id', $this->seller->id)
            ->first();

        $this->assertNotNull($conversation);
        $response->assertRedirect(route('chat.index', ['c' => $conversation->id]));
    }

    public function test_seller_cannot_start_chat_with_themself(): void
    {
        $this->actingAs($this->seller)
            ->post(route('chat.start'), [
                'listing_id' => $this->listing->id,
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('conversations', [
            'listing_id' => $this->listing->id,
            'buyer_id' => $this->seller->id,
            'seller_id' => $this->seller->id,
        ]);
    }

    public function test_buyer_can_start_chat_from_order_item(): void
    {
        $order = Order::create([
            'buyer_id' => $this->buyer->id,
            'order_number' => 'ORD-TEST-001',
            'total_amount' => 250.00,
            'status' => 'pago',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'listing_id' => $this->listing->id,
            'seller_id' => $this->seller->id,
            'unit_price' => 250.00,
            'quantity' => 1,
            'delivery_status' => 'em_entrega',
        ]);

        $response = $this->actingAs($this->buyer)->post(route('chat.start'), [
            'listing_id' => $this->listing->id,
        ]);

        $conversation = Conversation::where('listing_id', $this->listing->id)
            ->where('buyer_id', $this->buyer->id)
            ->first();

        $this->assertNotNull($conversation);
        $response->assertRedirect(route('chat.index', ['c' => $conversation->id]));
    }

    public function test_seller_can_start_chat_from_sale_item_with_buyer(): void
    {
        $order = Order::create([
            'buyer_id' => $this->buyer->id,
            'order_number' => 'ORD-TEST-002',
            'total_amount' => 250.00,
            'status' => 'pago',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'listing_id' => $this->listing->id,
            'seller_id' => $this->seller->id,
            'unit_price' => 250.00,
            'quantity' => 1,
            'delivery_status' => 'em_entrega',
        ]);

        $response = $this->actingAs($this->seller)->post(route('chat.start'), [
            'listing_id' => $this->listing->id,
            'buyer_id' => $this->buyer->id,
        ]);

        $conversation = Conversation::where('listing_id', $this->listing->id)
            ->where('buyer_id', $this->buyer->id)
            ->where('seller_id', $this->seller->id)
            ->first();

        $this->assertNotNull($conversation);
        $response->assertRedirect(route('chat.index', ['c' => $conversation->id]));
    }

    public function test_unauthorized_user_cannot_access_or_send_messages_to_conversation(): void
    {
        $conversation = Conversation::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'listing_id' => $this->listing->id,
        ]);

        $stranger = User::factory()->create(['email' => 'stranger@example.test']);

        $this->actingAs($stranger)
            ->getJson(route('chat.show', $conversation))
            ->assertForbidden();

        $this->actingAs($stranger)
            ->postJson(route('chat.messages.store', $conversation), [
                'body' => 'Tentativa não autorizada',
                'client_uuid' => 'unauth-uuid-1',
            ])
            ->assertForbidden();
    }

    public function test_admin_cannot_access_conversation_content_by_role(): void
    {
        $conversation = Conversation::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'listing_id' => $this->listing->id,
        ]);

        $admin = User::factory()->create([
            'name' => 'Administrador da Plataforma',
            'email' => 'admin@themerchant.local',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->getJson(route('chat.show', $conversation))
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson(route('chat.messages.store', $conversation), [
                'body' => 'Admin tentando postar',
                'client_uuid' => 'admin-uuid-1',
            ])
            ->assertForbidden();
    }

    public function test_message_sending_is_idempotent_by_client_uuid(): void
    {
        Event::fake([MessageSent::class]);

        $conversation = Conversation::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'listing_id' => $this->listing->id,
        ]);

        $payload = [
            'body' => 'Olá vendedor, o envio é imediato após a confirmação?',
            'client_uuid' => 'idempotent-uuid-12345',
        ];

        // First send
        $res1 = $this->actingAs($this->buyer)
            ->postJson(route('chat.messages.store', $conversation), $payload)
            ->assertStatus(201);

        $this->assertDatabaseCount('messages', 1);

        // Duplicate send with same client_uuid
        $res2 = $this->actingAs($this->buyer)
            ->postJson(route('chat.messages.store', $conversation), $payload)
            ->assertSuccessful();

        $this->assertDatabaseCount('messages', 1);
        $this->assertEquals($res1->json('message.id'), $res2->json('message.id'));

        Event::assertDispatched(MessageSent::class, 1);
    }

    public function test_message_validation_rejects_empty_or_oversized_body(): void
    {
        $conversation = Conversation::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'listing_id' => $this->listing->id,
        ]);

        // Empty body
        $this->actingAs($this->buyer)
            ->postJson(route('chat.messages.store', $conversation), [
                'body' => '   ',
                'client_uuid' => 'empty-uuid',
            ])
            ->assertUnprocessable();

        // Oversized body (> 2000 chars)
        $this->actingAs($this->buyer)
            ->postJson(route('chat.messages.store', $conversation), [
                'body' => str_repeat('a', 2001),
                'client_uuid' => 'large-uuid',
            ])
            ->assertUnprocessable();
    }

    public function test_read_cursor_advances_only_for_conversation_messages(): void
    {
        $conversation = Conversation::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'listing_id' => $this->listing->id,
        ]);

        $msg1 = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->seller->id,
            'client_uuid' => 'msg-1',
            'body' => 'Primeira mensagem do vendedor',
        ]);

        $msg2 = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->seller->id,
            'client_uuid' => 'msg-2',
            'body' => 'Segunda mensagem do vendedor',
        ]);

        $this->actingAs($this->buyer)
            ->patchJson(route('chat.read', $conversation), [
                'last_read_message_id' => $msg2->id,
            ])
            ->assertOk();

        $conversation->refresh();
        $this->assertEquals($msg2->id, $conversation->buyer_last_read_message_id);

        // Another conversation message shouldn't be accepted
        $otherListing = Listing::create([
            'seller_id' => $this->seller->id,
            'game_id' => $this->game->id,
            'category_id' => $this->category->id,
            'title' => 'Outro Anúncio de Teste',
            'slug' => 'outro-anuncio-de-teste',
            'description' => 'Outro produto.',
            'price' => 100.00,
            'status' => 'publicado',
        ]);

        $otherConv = Conversation::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'listing_id' => $otherListing->id,
        ]);
        $otherMsg = Message::create([
            'conversation_id' => $otherConv->id,
            'sender_id' => $this->seller->id,
            'client_uuid' => 'other-msg-1',
            'body' => 'Mensagem de outra conversa',
        ]);

        $this->actingAs($this->buyer)
            ->patchJson(route('chat.read', $conversation), [
                'last_read_message_id' => $otherMsg->id,
            ])
            ->assertUnprocessable();
    }

    public function test_privacy_does_not_leak_emails_in_chat_payloads(): void
    {
        $conversation = Conversation::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'listing_id' => $this->listing->id,
        ]);

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->seller->id,
            'client_uuid' => 'priv-1',
            'body' => 'Testando privacidade RNF07',
        ]);

        $safe = $msg->toSafeArray();
        $this->assertArrayNotHasKey('email', $safe);
        $this->assertArrayNotHasKey('sender_email', $safe);
        $this->assertEquals('Vendedor Pro', $safe['sender_name']);

        $response = $this->actingAs($this->buyer)
            ->getJson(route('chat.messages', $conversation))
            ->assertOk();

        $jsonString = $response->getContent();
        $this->assertStringNotContainsString('seller@example.test', $jsonString);
        $this->assertStringNotContainsString('buyer@example.test', $jsonString);
    }

    public function test_unread_counter_accurately_reflects_unread_messages(): void
    {
        $conversation = Conversation::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'listing_id' => $this->listing->id,
        ]);

        $this->actingAs($this->buyer)
            ->getJson(route('chat.unread-count'))
            ->assertOk()
            ->assertJson(['unread_count' => 0]);

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->seller->id,
            'client_uuid' => 'unread-1',
            'body' => 'Você tem nova mensagem!',
        ]);

        $this->actingAs($this->buyer)
            ->getJson(route('chat.unread-count'))
            ->assertOk()
            ->assertJson(['unread_count' => 1]);

        $this->actingAs($this->buyer)
            ->patchJson(route('chat.read', $conversation), [
                'last_read_message_id' => $msg->id,
            ])
            ->assertOk();

        $this->actingAs($this->buyer)
            ->getJson(route('chat.unread-count'))
            ->assertOk()
            ->assertJson(['unread_count' => 0]);
    }

    public function test_suspended_user_cannot_access_chat(): void
    {
        $this->buyer->update(['status' => 'suspended']);

        $this->actingAs($this->buyer)
            ->get(route('chat.index'))
            ->assertForbidden();
    }
}
