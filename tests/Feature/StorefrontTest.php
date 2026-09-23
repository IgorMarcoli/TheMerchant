<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_storefront_has_actionable_empty_states(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Seu próximo nível')
            ->assertSee('O próximo drop pode ser seu.')
            ->assertDontSee('Teste de Conexão');
    }

    public function test_storefront_only_shows_published_listings_and_active_games(): void
    {
        $this->createListing('Skin publicada', 'cosmetic', 'publicado');
        $this->createListing('Item em rascunho', 'cosmetic', 'rascunho');
        Game::create(['name' => 'Jogo inativo', 'slug' => 'inactive', 'active' => false]);

        $this->get('/')->assertOk()
            ->assertSee('Skin publicada')->assertSee('1 anúncio disponível')
            ->assertDontSee('Item em rascunho')->assertDontSee('Jogo inativo');
    }

    public function test_homepage_category_links_filter_the_catalog_and_preserve_selection(): void
    {
        $this->createListing('Skin publicada', 'cosmetic', 'publicado');
        $this->createListing('Aula de coaching', 'service', 'publicado');

        $this->get('/anuncios?tipo=service')->assertOk()
            ->assertSee('Aula de coaching')->assertDontSee('Skin publicada')
            ->assertSee('value="service" selected', false);
        $this->get('/anuncios?tipo=cosmetic&busca=Skin')->assertOk()
            ->assertSee('Skin publicada')->assertDontSee('Aula de coaching');
    }

    private function createListing(string $title, string $type, string $status): Listing
    {
        $slug = Str::slug($title);
        $seller = User::firstOrCreate(['email' => 'storefront@example.test'], [
            'name' => 'Vendedor de teste', 'password' => 'test-password', 'status' => 'active',
        ]);
        $seller->sellerProfile()->firstOrCreate([], ['status' => 'approved']);
        $game = Game::firstOrCreate(['slug' => 'test-game'], ['name' => 'Jogo de teste', 'active' => true]);
        $category = Category::firstOrCreate(['slug' => $type], [
            'game_id' => $game->id, 'name' => $type, 'type' => $type,
        ]);

        return Listing::create([
            'seller_id' => $seller->id, 'game_id' => $game->id, 'category_id' => $category->id,
            'title' => $title, 'slug' => $slug, 'description' => 'Descrição de teste',
            'price' => 99.90, 'status' => $status,
        ]);
    }
}
