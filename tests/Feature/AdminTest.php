<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Game;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Order;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Game $game;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'name' => 'Admin Chefe',
            'email' => 'admin@themerchant.local',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Usuario Comum',
            'email' => 'user@example.test',
        ]);

        $this->game = Game::create([
            'name' => 'Counter-Strike 2',
            'slug' => 'cs2',
            'active' => true,
        ]);

        $this->category = Category::create([
            'game_id'   => $this->game->id,
            'name'      => 'Skins de Rifles',
            'slug'      => 'skins-de-rifles',
            'type'      => 'cosmetic',
            'is_active' => true,
        ]);
    }

    public function test_visitor_cannot_access_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.categorias.index'))->assertRedirect(route('login'));
        $this->get(route('admin.usuarios.index'))->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        $this->actingAs($this->user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->user)->get(route('admin.categorias.index'))->assertForbidden();
        $this->actingAs($this->user)->get(route('admin.usuarios.index'))->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Painel de Controle Administrativo')
            ->assertSee('Volume Transacionado');
    }

    public function test_kpis_calculation_and_formulas(): void
    {
        // 1. Pedidos com diversos status
        // Pago em andamento ou concluído -> entram no volume transacionado
        Order::create(['order_number' => 'ORD-01', 'buyer_id' => $this->user->id, 'total_amount' => 150.00, 'status' => 'pago']);
        Order::create(['order_number' => 'ORD-02', 'buyer_id' => $this->user->id, 'total_amount' => 200.00, 'status' => 'em_andamento']);
        Order::create(['order_number' => 'ORD-03', 'buyer_id' => $this->user->id, 'total_amount' => 350.00, 'status' => 'concluido']);
        // Pendente e Cancelado -> estritamente excluídos do volume transacionado
        Order::create(['order_number' => 'ORD-04', 'buyer_id' => $this->user->id, 'total_amount' => 500.00, 'status' => 'pendente']);
        Order::create(['order_number' => 'ORD-05', 'buyer_id' => $this->user->id, 'total_amount' => 1000.00, 'status' => 'cancelado']);

        // 2. Anúncios
        $seller = User::factory()->seller()->create();
        Listing::create([
            'seller_id' => $seller->id,
            'game_id' => $this->game->id,
            'category_id' => $this->category->id,
            'title' => 'Anúncio Ativo',
            'slug' => 'anuncio-ativo',
            'description' => 'Teste',
            'price' => 100.00,
            'status' => 'publicado',
        ]);
        Listing::create([
            'seller_id' => $seller->id,
            'game_id' => $this->game->id,
            'category_id' => $this->category->id,
            'title' => 'Anúncio Pausado',
            'slug' => 'anuncio-pausado',
            'description' => 'Teste',
            'price' => 50.00,
            'status' => 'pausado',
        ]);

        // 3. Denúncia
        Report::create([
            'reporter_id' => $this->user->id,
            'listing_id' => 1,
            'reason' => 'Preço abusivo',
            'details' => 'Detalhes da suspeita',
            'status' => 'aberta',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

        /** @var array $metrics */
        $metrics = $response->viewData('metrics');

        // GMV bruto esperado: 150 + 200 + 350 = 700.00 (não soma 500 pendente nem 1000 cancelado)
        $this->assertEquals(700.00, $metrics['transacted_volume']);
        $this->assertEquals(5, $metrics['total_orders']);
        $this->assertEquals(3, $metrics['paid_orders']);
        $this->assertEquals(1, $metrics['pending_orders']);
        $this->assertEquals(1, $metrics['cancelled_orders']);

        $this->assertEquals(2, $metrics['total_listings']);
        $this->assertEquals(1, $metrics['active_listings']);
        $this->assertEquals(1, $metrics['open_reports']);
    }

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.categorias.store'), [
            'game_id' => $this->game->id,
            'name' => 'Facas Raras',
            'type' => 'cosmetic',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'name' => 'Facas Raras',
            'slug' => 'facas-raras',
            'game_id' => $this->game->id,
            'type' => 'cosmetic',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.categorias.update', $this->category), [
            'game_id' => $this->game->id,
            'name' => 'Skins de Rifles e Snipers',
            'type' => 'cosmetic',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'id' => $this->category->id,
            'name' => 'Skins de Rifles e Snipers',
            'slug' => 'skins-de-rifles-e-snipers',
        ]);
    }

    public function test_admin_can_toggle_category_status(): void
    {
        $this->assertTrue($this->category->is_active);

        $this->actingAs($this->admin)->patch(route('admin.categorias.status', $this->category))
            ->assertRedirect();

        $this->assertFalse($this->category->fresh()->is_active);

        $this->actingAs($this->admin)->patch(route('admin.categorias.status', $this->category))
            ->assertRedirect();

        $this->assertTrue($this->category->fresh()->is_active);
    }

    public function test_admin_can_toggle_game_status(): void
    {
        $this->assertTrue($this->game->active);

        $this->actingAs($this->admin)->patch(route('admin.games.status', $this->game))
            ->assertRedirect();

        $this->assertFalse($this->game->fresh()->active);

        $this->actingAs($this->admin)->patch(route('admin.games.status', $this->game))
            ->assertRedirect();

        $this->assertTrue($this->game->fresh()->active);
    }

    public function test_admin_cannot_delete_category_with_existing_listings(): void
    {
        $seller = User::factory()->seller()->create();
        Listing::create([
            'seller_id' => $seller->id,
            'game_id' => $this->game->id,
            'category_id' => $this->category->id,
            'title' => 'Anúncio Vinculado',
            'slug' => 'anuncio-vinculado',
            'description' => 'Teste de proteção',
            'price' => 120.00,
            'status' => 'publicado',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.categorias.destroy', $this->category));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $this->category->id]);
    }

    public function test_admin_can_delete_empty_category(): void
    {
        $emptyCategory = Category::create([
            'game_id' => $this->game->id,
            'name' => 'Categoria Sem Itens',
            'slug' => 'categoria-sem-itens',
            'type' => 'service',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.categorias.destroy', $emptyCategory));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $emptyCategory->id]);
    }

    public function test_admin_can_suspend_user_and_session_is_invalidated(): void
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->insert([
                'id' => 'test-session-token-123',
                'user_id' => $this->user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'payload-data',
                'last_activity' => time(),
            ]);
        }

        $response = $this->actingAs($this->admin)->put(route('admin.usuarios.update', $this->user), [
            'status' => 'suspended',
            'is_admin' => 0,
        ]);

        $response->assertRedirect();
        $this->assertEquals('suspended', $this->user->fresh()->status);

        if (Schema::hasTable('sessions')) {
            $this->assertDatabaseMissing('sessions', ['user_id' => $this->user->id]);
        }
    }

    public function test_suspended_user_with_existing_session_is_immediately_blocked_on_subsequent_requests(): void
    {
        // Usuário com sessão iniciada
        $this->actingAs($this->user)->get(route('cart.index'))->assertOk();

        // Admin suspende a conta do usuário
        $this->user->update(['status' => 'suspended']);

        // Próxima requisição na sessão existente é imediatamente barrada com 403
        $this->actingAs($this->user)->get(route('cart.index'))->assertForbidden();
    }

    public function test_suspended_user_cannot_access_chat_endpoints(): void
    {
        $this->user->update(['status' => 'suspended']);

        $this->actingAs($this->user)->get(route('chat.index'))->assertForbidden();
        $this->actingAs($this->user)->post(route('chat.start'), [
            'listing_id' => 1,
        ])->assertForbidden();
    }

    public function test_admin_cannot_access_private_chat_content_by_role(): void
    {
        $seller = User::factory()->seller()->create();
        $buyer = User::factory()->create();

        $listing = Listing::create([
            'seller_id' => $seller->id,
            'game_id' => $this->game->id,
            'category_id' => $this->category->id,
            'title' => 'Item Privado',
            'slug' => 'item-privado',
            'description' => 'Item exclusivo',
            'price' => 80.00,
            'status' => 'publicado',
        ]);

        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'listing_id' => $listing->id,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
            'client_uuid' => 'msg-priv-1',
            'body' => 'Conversa particular entre comprador e vendedor',
        ]);

        // Administrador tentando ver a conversa pelo simples papel de admin
        $this->actingAs($this->admin)
            ->getJson(route('chat.show', $conversation))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->postJson(route('chat.messages.store', $conversation), [
                'body' => 'Admin tentando invadir conversa',
                'client_uuid' => 'admin-intrusion',
            ])
            ->assertForbidden();
    }
}

