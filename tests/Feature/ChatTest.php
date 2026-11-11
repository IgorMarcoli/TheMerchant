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
use App\Services\ChatService;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use RuntimeException;
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
                'client_uuid' => '2f1c6f0e-6f4d-4c55-9b0a-1d2e3f405161',
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
                'client_uuid' => '3a2b1c0d-7e6f-4a5b-8c9d-0e1f2a3b4c5d',
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
            'client_uuid' => '9b7a6c5d-4e3f-4a1b-9c8d-7e6f5a4b3c2d',
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
                'client_uuid' => '0d1e2f3a-4b5c-4d6e-8f70-8192a3b4c5d6',
            ])
            ->assertUnprocessable();

        // Oversized body (> 2000 chars)
        $this->actingAs($this->buyer)
            ->postJson(route('chat.messages.store', $conversation), [
                'body' => str_repeat('a', 2001),
                'client_uuid' => '1e2f3a4b-5c6d-4e7f-8a9b-0c1d2e3f4a5b',
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

    public function test_chat_page_loads_its_interactive_script_and_safely_encodes_names(): void
    {
        $this->buyer->update(['name' => "Player \"D'Ávila\" <teste>"]);
        $this->actingAs($this->buyer)->post(route('chat.start'), ['listing_id' => $this->listing->id]);

        $this->get(route('chat.index'))
            ->assertOk()
            ->assertSee('function chatInbox(config)', false)
            ->assertSee('authName: '.Js::from($this->buyer->name), false)
            ->assertSee('Mensagens &amp; Negociações', false)
            ->assertDontSee('&amp;amp;', false);
    }

    public function test_buyer_and_seller_exchange_messages_without_mocking_broadcasts(): void
    {
        $this->actingAs($this->buyer)->post(route('chat.start'), ['listing_id' => $this->listing->id]);
        $conversation = Conversation::sole();

        $first = $this->postJson(route('chat.messages.store', $conversation), [
            'body' => 'Olá, podemos combinar a entrega?',
            'client_uuid' => '5c6d7e8f-9a0b-4c1d-8e2f-3a4b5c6d7e8f',
        ])->assertCreated()->json('message.id');

        $this->actingAs($this->seller)->getJson(route('chat.messages', $conversation))
            ->assertOk()->assertJsonPath('messages.0.id', $first);

        $second = $this->postJson(route('chat.messages.store', $conversation), [
            'body' => 'Sim, já estou disponível.',
            'client_uuid' => '6d7e8f9a-0b1c-4d2e-9f3a-4b5c6d7e8f9a',
        ])->assertCreated()->json('message.id');

        $this->actingAs($this->buyer)->getJson(route('chat.messages', [$conversation, 'after_id' => $first]))
            ->assertOk()->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.id', $second);
        $this->assertDatabaseCount('messages', 2);
    }

    public function test_seller_chat_form_is_independent_of_delivery_before_and_after_delivery(): void
    {
        $order = Order::create([
            'buyer_id' => $this->buyer->id,
            'order_number' => 'ORD-CHAT-FORM',
            'total_amount' => 250,
            'status' => 'pago',
        ]);
        $sale = OrderItem::create([
            'order_id' => $order->id,
            'listing_id' => $this->listing->id,
            'seller_id' => $this->seller->id,
            'unit_price' => 250,
            'quantity' => 1,
            'delivery_status' => 'em_entrega',
        ]);

        foreach (['em_entrega', 'entregue'] as $status) {
            $sale->update(['delivery_status' => $status]);
            $html = $this->actingAs($this->seller)->get(route('seller.sales.index'))->assertOk()->getContent();
            $document = new \DOMDocument();
            @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new \DOMXPath($document);
            $forms = $xpath->query('//form[@action="'.route('chat.start').'"]');
            $this->assertCount(1, $forms);
            $form = $forms->item(0);
            $this->assertSame('POST', $form->getAttribute('method'));
            $this->assertSame(0, $xpath->query('.//input[@name="_method"]', $form)->length);
            $this->assertSame((string) $this->buyer->id, $xpath->query('.//input[@name="buyer_id"]', $form)->item(0)->getAttribute('value'));
            $this->assertSame((string) $this->listing->id, $xpath->query('.//input[@name="listing_id"]', $form)->item(0)->getAttribute('value'));
        }
    }

    public function test_broadcast_carries_only_ids_on_private_channel_after_commit(): void
    {
        $conversation = $this->conversation();
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->buyer->id,
            'client_uuid' => (string) Str::uuid(),
            'body' => 'Conteúdo que não pode ir para o WebSocket',
        ]);

        $event = new MessageSent($message);

        $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $event);
        $this->assertSame(['private-chat.'.$conversation->id], array_map(fn ($channel) => $channel->name, $event->broadcastOn()));
        $this->assertSame([
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'sender_id' => $this->buyer->id,
        ], $event->broadcastWith());
    }

    public function test_rolled_back_message_is_neither_persisted_nor_signaled(): void
    {
        $conversation = $this->conversation();
        $signals = 0;
        Event::listen(MessageSent::class, function () use (&$signals): void {
            $signals++;
        });

        try {
            DB::transaction(function () use ($conversation): void {
                app(ChatService::class)->sendMessage($this->buyer, $conversation, 'Vai falhar', (string) Str::uuid());
                throw new RuntimeException('falha depois de gravar');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame(0, $signals);
        $this->assertDatabaseCount('messages', 0);

        app(ChatService::class)->sendMessage($this->buyer, $conversation, 'Agora vai', (string) Str::uuid());

        $this->assertSame(1, $signals);
    }

    public function test_message_requires_uuid_and_plain_text(): void
    {
        $conversation = $this->conversation();

        $this->actingAs($this->buyer)
            ->postJson(route('chat.messages.store', $conversation), ['body' => 'Olá', 'client_uuid' => 'nao-e-uuid'])
            ->assertUnprocessable()->assertJsonValidationErrors('client_uuid');

        $this->postJson(route('chat.messages.store', $conversation), ['body' => "Olá\x07", 'client_uuid' => (string) Str::uuid()])
            ->assertUnprocessable()->assertJsonValidationErrors('body');

        $this->postJson(route('chat.messages.store', $conversation), ['body' => "Linha 1\nLinha 2 <b>sem HTML</b>", 'client_uuid' => (string) Str::uuid()])
            ->assertCreated()->assertJsonPath('message.body', "Linha 1\nLinha 2 <b>sem HTML</b>");
    }

    public function test_sending_is_rate_limited_per_account(): void
    {
        $conversation = $this->conversation();
        $this->actingAs($this->buyer);

        for ($i = 1; $i <= 20; $i++) {
            $this->postJson(route('chat.messages.store', $conversation), ['body' => "Mensagem {$i}", 'client_uuid' => (string) Str::uuid()])
                ->assertCreated();
        }

        $this->postJson(route('chat.messages.store', $conversation), ['body' => 'Excesso', 'client_uuid' => (string) Str::uuid()])
            ->assertTooManyRequests();
        $this->assertDatabaseCount('messages', 20);
    }

    public function test_history_is_paginated_for_older_messages_and_reconnection(): void
    {
        $conversation = $this->conversation();
        $ids = [];
        for ($i = 1; $i <= 60; $i++) {
            $ids[] = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $i % 2 ? $this->buyer->id : $this->seller->id,
                'client_uuid' => (string) Str::uuid(),
                'body' => "Mensagem {$i}",
            ])->id;
        }

        $this->actingAs($this->buyer);

        $latest = $this->getJson(route('chat.messages', $conversation))->assertOk()
            ->assertJsonCount(50, 'messages')->assertJsonPath('has_more', true);
        $this->assertSame($ids[10], $latest->json('messages.0.id'));
        $this->assertSame($ids[59], $latest->json('messages.49.id'));

        $this->getJson(route('chat.messages', [$conversation, 'before_id' => $ids[10]]))->assertOk()
            ->assertJsonCount(10, 'messages')->assertJsonPath('has_more', false)
            ->assertJsonPath('messages.0.id', $ids[0]);

        // Reconexão: recupera tudo depois do último id conhecido, em páginas.
        $this->getJson(route('chat.messages', [$conversation, 'after_id' => 0]))->assertOk()
            ->assertJsonCount(50, 'messages')->assertJsonPath('has_more', true);
        $this->getJson(route('chat.messages', [$conversation, 'after_id' => $ids[49]]))->assertOk()
            ->assertJsonCount(10, 'messages')->assertJsonPath('has_more', false);

        $this->getJson(route('chat.messages', [$conversation, 'after_id' => 1, 'before_id' => 5]))->assertUnprocessable();

        $this->get(route('chat.index', ['c' => $conversation->id]))->assertOk()
            ->assertSee('hasOlder: true', false)
            ->assertSee('Mensagem 60')
            ->assertDontSee('Mensagem 10"', false);
    }

    public function test_private_channel_is_authorized_only_for_active_participants(): void
    {
        $conversation = $this->conversation();
        $stranger = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->authorizeChannel($this->buyer, $conversation)->assertOk()->assertJsonStructure(['auth']);
        $this->authorizeChannel($this->seller, $conversation)->assertOk();
        $this->authorizeChannel($stranger, $conversation)->assertForbidden();
        $this->authorizeChannel($admin, $conversation)->assertForbidden();

        $this->actingAs($stranger)->getJson(route('chat.messages', $conversation))->assertForbidden();

        $this->buyer->update(['status' => 'suspended']);
        $this->authorizeChannel($this->buyer, $conversation)->assertForbidden();
    }

    public function test_guest_cannot_authorize_private_channel(): void
    {
        $conversation = $this->conversation();
        $this->useReverbBroadcaster();

        $this->postJson('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-chat.'.$conversation->id])
            ->assertUnauthorized();
    }

    public function test_suspension_revokes_read_send_and_reconnection_for_an_established_session(): void
    {
        $this->actingAs($this->buyer)->post(route('chat.start'), ['listing_id' => $this->listing->id]);
        $conversation = Conversation::sole();

        $this->postJson(route('chat.messages.store', $conversation), ['body' => 'Oi, vendedor', 'client_uuid' => (string) Str::uuid()])
            ->assertCreated();
        $this->actingAs($this->seller)->getJson(route('chat.messages', $conversation))->assertOk()->assertJsonCount(1, 'messages');
        $this->authorizeChannel($this->seller, $conversation)->assertOk();

        // Ação administrativa (#22) usando a regra de conta ativa (#2).
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->put(route('admin.usuarios.update', $this->seller), ['status' => 'suspended', 'is_admin' => 0])
            ->assertRedirect();

        // A sessão já aberta do vendedor perde leitura, envio, cursor e reassinatura do canal.
        $this->actingAs($this->seller)->getJson(route('chat.messages', $conversation))->assertForbidden();
        $this->postJson(route('chat.messages.store', $conversation), ['body' => 'Ainda estou aqui', 'client_uuid' => (string) Str::uuid()])
            ->assertForbidden();
        $this->patchJson(route('chat.read', $conversation), ['last_read_message_id' => Message::first()->id])->assertForbidden();
        $this->authorizeChannel($this->seller, $conversation)->assertForbidden();

        // O comprador continua com o histórico; a mensagem nova não é exposta ao suspenso.
        $this->actingAs($this->buyer)->getJson(route('chat.messages', $conversation))->assertOk()->assertJsonCount(1, 'messages');
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_buyer_resumes_after_purchase_but_cannot_open_new_chat_on_unavailable_listing(): void
    {
        $this->listing->update(['status' => 'vendido']);

        $this->actingAs($this->buyer)->post(route('chat.start'), ['listing_id' => $this->listing->id])
            ->assertSessionHasErrors('conversation');
        $this->assertDatabaseCount('conversations', 0);

        $this->paidOrder('pago');

        $this->post(route('chat.start'), ['listing_id' => $this->listing->id])
            ->assertRedirect(route('chat.index', ['c' => Conversation::sole()->id]));
    }

    public function test_seller_cannot_open_chat_with_buyer_whose_order_is_unpaid(): void
    {
        $this->paidOrder('pendente');

        $this->actingAs($this->seller)->post(route('chat.start'), ['listing_id' => $this->listing->id, 'buyer_id' => $this->buyer->id])
            ->assertSessionHasErrors('conversation');
        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_chat_page_loads_echo_only_when_reverb_is_configured_without_leaking_secret(): void
    {
        $this->actingAs($this->buyer)->post(route('chat.start'), ['listing_id' => $this->listing->id]);
        $this->get(route('chat.index'))->assertOk()->assertDontSee('laravel-echo@', false);

        $this->useReverbBroadcaster();

        $this->get(route('chat.index'))->assertOk()
            ->assertSee('laravel-echo@2.1.6', false)
            ->assertSee('test-key', false)
            ->assertDontSee('test-secret', false);
    }

    private function conversation(): Conversation
    {
        return Conversation::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'listing_id' => $this->listing->id,
        ]);
    }

    private function paidOrder(string $status): void
    {
        $order = Order::create([
            'buyer_id' => $this->buyer->id,
            'order_number' => 'ORD-'.Str::upper(Str::random(8)),
            'total_amount' => 250,
            'status' => $status,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'listing_id' => $this->listing->id,
            'seller_id' => $this->seller->id,
            'unit_price' => 250,
            'quantity' => 1,
            'delivery_status' => 'em_entrega',
        ]);
    }

    private function useReverbBroadcaster(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
            'broadcasting.connections.reverb.options.host' => 'localhost',
        ]);

        // Os canais foram registrados no driver do boot; registra de novo no Reverb.
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');
    }

    private function authorizeChannel(User $user, Conversation $conversation): \Illuminate\Testing\TestResponse
    {
        $this->useReverbBroadcaster();

        return $this->actingAs($user)->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-chat.'.$conversation->id,
        ]);
    }
}
