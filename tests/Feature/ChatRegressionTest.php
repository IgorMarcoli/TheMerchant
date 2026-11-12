<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Game;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use stdClass;
use Tests\TestCase;

/**
 * Regressão do chat (#32): isolamento entre conversas, concorrência, idempotência,
 * cursores independentes, matriz de acesso HTTP, XSS e limites.
 * O fluxo com Reverb e worker reais fica no roteiro manual de docs/CHAT_TEMPO_REAL.md.
 */
class ChatRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private User $buyer;

    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = $this->sellerUser('Vendedor Regressao', 'seller-reg@example.test');
        $this->buyer = User::factory()->create(['name' => 'Comprador Regressao', 'email' => 'buyer-reg@example.test']);
        $this->listing = $this->listingFor($this->seller, 'Skin Regressao');
    }

    public function test_concurrent_conversation_creation_returns_the_single_existing_conversation(): void
    {
        $participants = [
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'listing_id' => $this->listing->id,
        ];

        // Outra requisição grava a mesma conversa logo depois do SELECT do firstOrCreate.
        $raced = $this->raceAfterSelect('conversations', fn () => DB::table('conversations')
            ->insert($participants + ['created_at' => now(), 'updated_at' => now()]));

        $conversation = app(ChatService::class)->startOrGetConversation($this->buyer, $this->listing->id);

        $this->assertTrue($raced->value);
        $this->assertDatabaseCount('conversations', 1);
        $this->assertSame(Conversation::sole()->id, $conversation->id);

        // Nova abertura pela rota devolve a mesma conversa.
        $this->actingAs($this->buyer)->post(route('chat.start'), ['listing_id' => $this->listing->id])
            ->assertRedirect(route('chat.index', ['c' => $conversation->id]));
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_database_rejects_duplicate_conversation_and_duplicate_client_uuid(): void
    {
        $conversation = $this->conversation();
        $uuid = (string) Str::uuid();
        $this->message($conversation, $this->buyer, 'Primeira', $uuid);

        $this->assertThrows(fn () => $this->conversation(), UniqueConstraintViolationException::class);
        $this->assertThrows(
            fn () => $this->message($conversation, $this->buyer, 'Repetida', $uuid),
            UniqueConstraintViolationException::class
        );
    }

    public function test_concurrent_retry_with_same_uuid_creates_one_message_and_one_signal(): void
    {
        Event::fake([MessageSent::class]);
        $conversation = $this->conversation();
        $uuid = (string) Str::uuid();

        // A tentativa anterior grava logo depois do SELECT desta, antes do INSERT.
        $raced = $this->raceAfterSelect('messages', fn () => DB::table('messages')->insert([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->buyer->id,
            'client_uuid' => $uuid,
            'body' => 'Chegou?',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $this->actingAs($this->buyer)
            ->postJson(route('chat.messages.store', $conversation), ['body' => 'Chegou?', 'client_uuid' => $uuid])
            ->assertOk()
            ->assertJsonPath('message.client_uuid', $uuid);

        $this->assertTrue($raced->value);
        $this->assertDatabaseCount('messages', 1);
        Event::assertNotDispatched(MessageSent::class);
    }

    public function test_same_uuid_is_scoped_by_sender_and_conversation(): void
    {
        $conversation = $this->conversation();
        $other = $this->conversation($this->listingFor($this->seller, 'Outra Skin'));
        $uuid = (string) Str::uuid();

        $this->actingAs($this->buyer)
            ->postJson(route('chat.messages.store', $conversation), ['body' => 'Do comprador', 'client_uuid' => $uuid])
            ->assertCreated();
        $this->postJson(route('chat.messages.store', $other), ['body' => 'Em outra conversa', 'client_uuid' => $uuid])
            ->assertCreated();
        $this->actingAs($this->seller)
            ->postJson(route('chat.messages.store', $conversation), ['body' => 'Do vendedor', 'client_uuid' => $uuid])
            ->assertCreated();

        $this->assertDatabaseCount('messages', 3);
    }

    public function test_messages_never_leak_between_conversations(): void
    {
        $secondSeller = $this->sellerUser('Outro Vendedor', 'seller-two@example.test');
        $mine = $this->conversation();
        $theirs = Conversation::create([
            'buyer_id' => User::factory()->create()->id,
            'seller_id' => $secondSeller->id,
            'listing_id' => $this->listingFor($secondSeller, 'Skin Alheia')->id,
        ]);

        $this->message($mine, $this->seller, 'Mensagem da minha conversa');
        $secret = $this->message($theirs, $secondSeller, 'Segredo de outra conversa');

        $this->actingAs($this->buyer);

        foreach ([[], ['after_id' => 0], ['before_id' => $secret->id + 1]] as $query) {
            $this->getJson(route('chat.messages', [$mine, ...$query]))
                ->assertOk()
                ->assertJsonCount(1, 'messages')
                ->assertDontSee('Segredo de outra conversa');
        }

        $this->get(route('chat.index', ['c' => $theirs->id]))
            ->assertOk()
            ->assertDontSee('Segredo de outra conversa')
            ->assertSee('Mensagem da minha conversa');
        $this->getJson(route('chat.messages', $theirs))->assertForbidden();
        $this->patchJson(route('chat.read', $mine), ['last_read_message_id' => $secret->id])->assertUnprocessable();
    }

    public function test_read_cursors_are_independent_per_participant_and_never_go_back(): void
    {
        $conversation = $this->conversation();
        $chat = app(ChatService::class);

        $first = $chat->sendMessage($this->buyer, $conversation, 'Oi', (string) Str::uuid());
        $conversation->refresh();

        // Quem envia já leu a própria mensagem; o outro participante, não.
        $this->assertSame($first->id, $conversation->buyer_last_read_message_id);
        $this->assertNull($conversation->seller_last_read_message_id);
        $this->assertSame(0, $chat->getUnreadCountForUser($this->buyer));
        $this->assertSame(1, $chat->getUnreadCountForUser($this->seller));

        $second = $chat->sendMessage($this->seller, $conversation, 'Olá!', (string) Str::uuid());
        $third = $chat->sendMessage($this->seller, $conversation, 'Tudo certo?', (string) Str::uuid());

        $this->actingAs($this->buyer)
            ->patchJson(route('chat.read', $conversation), ['last_read_message_id' => $third->id])
            ->assertOk()->assertJsonPath('unread_count', 0);
        $this->patchJson(route('chat.read', $conversation), ['last_read_message_id' => $second->id])
            ->assertOk();

        $conversation->refresh();
        $this->assertSame($third->id, $conversation->buyer_last_read_message_id);
        $this->assertSame($third->id, $conversation->seller_last_read_message_id);
        $this->assertSame(0, $chat->getUnreadCountForUser($this->seller));
    }

    public function test_unread_counter_aggregates_conversations_and_ignores_own_messages(): void
    {
        $secondSeller = $this->sellerUser('Segundo Vendedor', 'seller-three@example.test');
        $first = $this->conversation();
        $second = Conversation::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $secondSeller->id,
            'listing_id' => $this->listingFor($secondSeller, 'Skin Dois')->id,
        ]);
        // O comprador também vende: conversa em que ele é o vendedor.
        $this->buyer->sellerProfile()->create(['status' => 'approved']);
        $asSeller = Conversation::create([
            'buyer_id' => $this->seller->id,
            'seller_id' => $this->buyer->id,
            'listing_id' => $this->listingFor($this->buyer, 'Skin do Comprador')->id,
        ]);

        $this->message($first, $this->seller, 'A');
        $this->message($first, $this->seller, 'B');
        $this->message($first, $this->buyer, 'Minha resposta');
        $this->message($second, $secondSeller, 'C');
        $this->message($asSeller, $this->seller, 'D');

        $this->actingAs($this->buyer)->getJson(route('chat.unread-count'))->assertOk()->assertJson(['unread_count' => 4]);
        $this->assertSame(2, $first->unreadCountFor($this->buyer));
        $this->assertSame(1, $second->unreadCountFor($this->buyer));
        $this->assertSame(1, $asSeller->unreadCountFor($this->buyer));

        $outsider = User::factory()->create();
        $this->actingAs($outsider)->getJson(route('chat.unread-count'))->assertOk()->assertJson(['unread_count' => 0]);
    }

    public function test_http_access_matrix_for_visitor_stranger_suspended_and_participant(): void
    {
        $conversation = $this->conversation();
        $message = $this->message($conversation, $this->seller, 'Mensagem protegida');
        $stranger = User::factory()->create();
        $suspended = User::factory()->create(['status' => 'suspended']);
        $suspendedConversation = Conversation::create([
            'buyer_id' => $suspended->id,
            'seller_id' => $this->seller->id,
            'listing_id' => $this->listing->id,
        ]);

        $requests = fn (Conversation $target): array => [
            ['getJson', route('chat.show', $target), []],
            ['getJson', route('chat.messages', $target), []],
            ['postJson', route('chat.messages.store', $target), ['body' => 'Oi', 'client_uuid' => (string) Str::uuid()]],
            ['patchJson', route('chat.read', $target), ['last_read_message_id' => $message->id]],
        ];

        foreach ($requests($conversation) as [$method, $url, $data]) {
            $this->{$method}($url, $data)->assertUnauthorized();
        }
        $this->getJson(route('chat.unread-count'))->assertUnauthorized();
        $this->postJson(route('chat.start'), ['listing_id' => $this->listing->id])->assertUnauthorized();

        foreach ($requests($conversation) as [$method, $url, $data]) {
            $this->actingAs($stranger)->{$method}($url, $data)->assertForbidden();
        }

        foreach ($requests($suspendedConversation) as [$method, $url, $data]) {
            $this->actingAs($suspended)->{$method}($url, $data)->assertForbidden();
        }
        $this->actingAs($suspended)->getJson(route('chat.unread-count'))->assertForbidden();
        $this->actingAs($suspended)->postJson(route('chat.start'), ['listing_id' => $this->listing->id])->assertForbidden();

        $this->actingAs($this->buyer)->getJson(route('chat.show', $conversation))->assertOk();
        $this->getJson(route('chat.messages', $conversation))->assertOk()->assertJsonPath('messages.0.body', 'Mensagem protegida');
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_reactivated_account_regains_access_without_losing_history(): void
    {
        $conversation = $this->conversation();
        $this->message($conversation, $this->seller, 'Histórico preservado');

        $this->buyer->update(['status' => 'suspended']);
        $this->actingAs($this->buyer)->getJson(route('chat.messages', $conversation))->assertForbidden();

        $this->buyer->update(['status' => 'active']);
        $this->getJson(route('chat.messages', $conversation))
            ->assertOk()->assertJsonCount(1, 'messages')->assertJsonPath('messages.0.body', 'Histórico preservado');
    }

    public function test_message_content_is_escaped_in_inbox_preview_and_initial_payload(): void
    {
        $payload = '<script>alert("xss")</script><img src=x onerror=alert(1)>';
        $this->listing->update(['title' => 'Skin <script>alert("titulo")</script>']);
        $this->seller->update(['name' => 'Vendedor <img src=x onerror=alert(2)>']);
        $conversation = $this->conversation();
        $this->message($conversation, $this->seller, $payload);

        $html = $this->actingAs($this->buyer)->get(route('chat.index', ['c' => $conversation->id]))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->getContent();

        $this->assertStringNotContainsString('<script>alert(', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        // O texto só é exibido por x-text (texto puro), nunca por x-html.
        $this->assertStringNotContainsString('x-html', $html);

        // A API devolve o texto como dado; quem renderiza é o x-text.
        $this->getJson(route('chat.messages', $conversation))->assertOk()->assertJsonPath('messages.0.body', $payload);
    }

    public function test_avatar_initial_keeps_accented_names_intact(): void
    {
        $this->seller->update(['name' => 'Ávila Vendas']);
        $conversation = $this->conversation();

        $html = $this->actingAs($this->buyer)->get(route('chat.index', ['c' => $conversation->id]))
            ->assertOk()
            ->getContent();

        $this->assertTrue(mb_check_encoding($html, 'UTF-8'));
        $this->assertStringNotContainsString("\u{FFFD}", $html);
        $this->assertMatchesRegularExpression('/>\s*Á\s*</u', $html);
    }

    public function test_body_boundaries_are_trimmed_and_counted_in_characters(): void
    {
        $conversation = $this->conversation();
        $this->actingAs($this->buyer);

        $this->postJson(route('chat.messages.store', $conversation), ['body' => str_repeat('a', 2000), 'client_uuid' => (string) Str::uuid()])
            ->assertCreated();
        $this->postJson(route('chat.messages.store', $conversation), ['body' => str_repeat('é', 2000), 'client_uuid' => (string) Str::uuid()])
            ->assertCreated();
        $this->postJson(route('chat.messages.store', $conversation), ['body' => str_repeat('é', 2001), 'client_uuid' => (string) Str::uuid()])
            ->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->postJson(route('chat.messages.store', $conversation), ['body' => "  oi  \n", 'client_uuid' => (string) Str::uuid()])
            ->assertCreated()->assertJsonPath('message.body', 'oi');
        $this->postJson(route('chat.messages.store', $conversation), ['body' => "\n\t ", 'client_uuid' => (string) Str::uuid()])
            ->assertUnprocessable();

        $this->assertDatabaseCount('messages', 3);
    }

    public function test_opening_conversations_is_rate_limited_per_account(): void
    {
        $this->actingAs($this->buyer);

        for ($i = 1; $i <= 10; $i++) {
            $this->post(route('chat.start'), ['listing_id' => $this->listing->id])->assertRedirect();
        }

        $this->post(route('chat.start'), ['listing_id' => $this->listing->id])->assertTooManyRequests();
        $this->assertDatabaseCount('conversations', 1);

        // O limite é por conta: outro usuário não é afetado.
        $this->actingAs(User::factory()->create())
            ->post(route('chat.start'), ['listing_id' => $this->listing->id])->assertRedirect();
    }

    public function test_sent_event_carries_no_content_or_personal_data(): void
    {
        Event::fake([MessageSent::class]);
        $conversation = $this->conversation();

        $this->actingAs($this->buyer)->postJson(route('chat.messages.store', $conversation), [
            'body' => 'Meu contato secreto 11 99999-0000',
            'client_uuid' => (string) Str::uuid(),
        ])->assertCreated();

        Event::assertDispatched(MessageSent::class, function (MessageSent $event): bool {
            $serialized = json_encode([$event->broadcastWith(), get_object_vars($event)]);

            return array_keys($event->broadcastWith()) === ['conversation_id', 'message_id', 'sender_id']
                && ! str_contains($serialized, 'secreto')
                && ! str_contains($serialized, 'buyer-reg@example.test')
                && ! str_contains($serialized, 'Comprador Regressao');
        });
    }

    /**
     * Executa $concurrentWrite uma vez, logo após o primeiro "select * ... limit 1" na tabela e
     * fora do savepoint do createOrFirst: reproduz outra requisição que gravou antes do INSERT.
     */
    private function raceAfterSelect(string $table, Closure $concurrentWrite): stdClass
    {
        $raced = (object) ['value' => false];

        DB::listen(function (QueryExecuted $query) use ($raced, $table, $concurrentWrite): void {
            $sql = strtolower($query->sql);

            if (! $raced->value
                && str_starts_with($sql, 'select *')
                && str_contains($sql, 'from "'.$table.'"')
                && str_contains($sql, 'limit 1')) {
                $raced->value = true;
                $concurrentWrite();
            }
        });

        return $raced;
    }

    private function sellerUser(string $name, string $email): User
    {
        $user = User::factory()->create(['name' => $name, 'email' => $email]);
        $user->sellerProfile()->create(['status' => 'approved']);

        return $user;
    }

    private function listingFor(User $seller, string $title): Listing
    {
        $game = Game::firstOrCreate(['slug' => 'cs2'], ['name' => 'Counter-Strike 2', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'skins'], ['game_id' => $game->id, 'name' => 'Skins']);

        return Listing::create([
            'seller_id' => $seller->id,
            'game_id' => $game->id,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'description' => 'Anúncio de teste do chat.',
            'price' => 100,
            'status' => 'publicado',
        ]);
    }

    private function conversation(?Listing $listing = null): Conversation
    {
        $listing ??= $this->listing;

        return Conversation::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $listing->seller_id,
            'listing_id' => $listing->id,
        ]);
    }

    private function message(Conversation $conversation, User $sender, string $body, ?string $uuid = null): Message
    {
        return Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'client_uuid' => $uuid ?? (string) Str::uuid(),
            'body' => $body,
        ]);
    }
}
