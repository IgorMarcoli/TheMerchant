<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * #24: proteção CSRF real (o Laravel a desliga em testes) e escape nativo do Blade
 * em todas as telas que exibem texto digitado por usuários.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD = '<script>alert(1)</script>';

    private const ESCAPED = '&lt;script&gt;alert(1)&lt;/script&gt;';

    public function test_state_changing_forms_require_a_csrf_token(): void
    {
        $user = User::factory()->seller()->create();
        $listing = $this->listing($user, 'Skin protegida');
        $this->enforceCsrf();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(419);
        $this->post('/cadastro', ['name' => 'Novo', 'email' => 'novo@example.test'])->assertStatus(419);

        $this->actingAs($user);
        $this->post(route('logout'))->assertStatus(419);
        $this->put(route('profile.update'), ['name' => 'Alterado'])->assertStatus(419);
        $this->post(route('cart.add', $listing))->assertStatus(419);
        $this->patch(route('seller.anuncios.status', $listing))->assertStatus(419);
        $this->postJson(route('chat.start'), ['listing_id' => $listing->id])->assertStatus(419);

        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertSame('publicado', $listing->fresh()->status);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('conversations', 0);
        $this->assertAuthenticatedAs($user);
    }

    public function test_valid_csrf_token_is_accepted(): void
    {
        $user = User::factory()->create(['name' => 'Nome antigo']);
        $this->enforceCsrf();

        $this->actingAs($user)
            ->withSession(['_token' => 'token-de-teste'])
            ->put(route('profile.update'), ['_token' => 'token-de-teste', 'name' => 'Nome novo', 'email' => $user->email])
            ->assertRedirect();

        $this->assertSame('Nome novo', $user->fresh()->name);
    }

    public function test_payment_webhook_is_outside_csrf_and_relies_on_its_secret(): void
    {
        $this->enforceCsrf();

        $this->postJson(route('api.webhooks.payment'), ['event' => 'billing.paid'])->assertUnauthorized();
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_every_form_rendered_for_a_logged_user_carries_a_csrf_token(): void
    {
        $seller = User::factory()->seller()->create();
        $this->listing($seller, 'Skin com formulario');
        $this->actingAs($seller);
        $checked = 0;

        foreach (['home', 'cart.index', 'orders.index', 'profile.edit', 'seller.anuncios.index', 'seller.sales.index', 'chat.index'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();
            preg_match_all('/<form\b[^>]*method="post"[^>]*>(.*?)<\/form>/is', $html, $forms);

            foreach ($forms[1] as $body) {
                $this->assertStringContainsString('name="_token"', $body, "Formulário sem @csrf em {$route}.");
                $checked++;
            }
        }

        // Garante que a varredura encontrou formulários de fato (logout, perfil, anúncios...).
        $this->assertGreaterThanOrEqual(3, $checked);
    }

    public function test_user_content_is_escaped_on_public_pages(): void
    {
        $seller = User::factory()->seller()->create(['name' => 'Loja '.self::PAYLOAD]);
        $listing = $this->listing($seller, 'Skin '.self::PAYLOAD, 'Descricao '.self::PAYLOAD);

        foreach ([route('home'), route('listings.index'), route('listings.index', ['busca' => self::PAYLOAD]), route('listings.show', $listing->slug)] as $url) {
            $this->assertEscaped($this->get($url), $url);
        }
    }

    public function test_user_content_is_escaped_on_order_sales_and_admin_pages(): void
    {
        $seller = User::factory()->seller()->create(['name' => 'Vendedor '.self::PAYLOAD]);
        $buyer = User::factory()->create(['name' => 'Comprador '.self::PAYLOAD]);
        $listing = $this->listing($seller, 'Skin '.self::PAYLOAD);
        $order = Order::create(['order_number' => 'ORD-XSS', 'buyer_id' => $buyer->id, 'total_amount' => 50, 'status' => 'pago']);
        OrderItem::create([
            'order_id' => $order->id,
            'listing_id' => $listing->id,
            'seller_id' => $seller->id,
            'unit_price' => 50,
            'quantity' => 1,
            'delivery_status' => 'em_entrega',
        ]);

        $this->assertEscaped($this->actingAs($buyer)->get(route('orders.index')), 'orders.index');
        $this->assertEscaped($this->get(route('orders.show', $order)), 'orders.show');
        $this->assertEscaped($this->actingAs($seller)->get(route('seller.sales.index')), 'seller.sales.index');
        $this->assertEscaped($this->actingAs(User::factory()->admin()->create())->get(route('admin.usuarios.index')), 'admin.usuarios.index');
    }

    private function assertEscaped(TestResponse $response, string $where): void
    {
        $html = $response->assertOk()->getContent();

        $this->assertStringNotContainsString(self::PAYLOAD, $html, "Conteúdo sem escape em {$where}.");
        $this->assertStringContainsString(self::ESCAPED, $html, "Conteúdo esperado não exibido em {$where}.");
    }

    /**
     * O middleware de CSRF ignora a checagem quando APP_ENV=testing; aqui ela volta a valer.
     */
    private function enforceCsrf(): void
    {
        $this->app['env'] = 'local';
    }

    private function listing(User $seller, string $title, string $description = 'Oferta de teste.'): Listing
    {
        $game = Game::firstOrCreate(['slug' => 'cs2'], ['name' => 'Counter-Strike 2', 'is_active' => true]);
        $category = Category::firstOrCreate(
            ['slug' => 'skins'],
            ['game_id' => $game->id, 'name' => 'Skins', 'type' => 'cosmetic', 'is_active' => true]
        );

        return Listing::create([
            'seller_id' => $seller->id,
            'game_id' => $game->id,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => 'oferta-'.Listing::count(),
            'description' => $description,
            'price' => 50,
            'status' => 'publicado',
        ]);
    }
}
