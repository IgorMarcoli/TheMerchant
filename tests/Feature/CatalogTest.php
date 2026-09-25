<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private function listing(string $title, float $price = 50, int $reviews = 0, float $rating = 0): Listing
    {
        $seller = User::factory()->create(['status' => 'active']);
        $seller->sellerProfile()->create(['status' => 'approved', 'total_reviews' => $reviews, 'reputation_score' => $rating]);
        $game = Game::firstOrCreate(['slug' => 'catalog-game'], ['name' => 'Jogo', 'active' => true]);
        $category = Category::firstOrCreate(['slug' => 'catalog-category'], [
            'name' => 'Coaching', 'type' => 'service', 'game_id' => $game->id,
        ]);

        return Listing::create([
            'title' => $title, 'slug' => 'listing-'.$seller->id, 'description' => 'Descrição',
            'seller_id' => $seller->id, 'game_id' => $game->id, 'category_id' => $category->id,
            'price' => $price, 'status' => 'publicado',
        ]);
    }

    public function test_combined_filters_and_pagination_preserve_the_query(): void
    {
        for ($i = 0; $i < 13; $i++) {
            $listing = $this->listing('Coaching '.$i, 60, 2, 4.5);
        }
        $this->listing('Fora da faixa', 100, 2, 5);
        $this->listing('Sem reputação', 60);
        $this->listing('Bloqueado', 60, 2, 5)->update(['status' => 'bloqueado']);
        $this->listing('Pausado', 60, 2, 5)->update(['status' => 'pausado']);
        $suspended = $this->listing('Vendedor suspenso', 60, 2, 5);
        $suspended->seller->update(['status' => 'suspended']);

        $filters = [
            'busca' => 'Coaching', 'tipo' => 'service', 'jogo' => $listing->game_id,
            'categoria' => $listing->category_id, 'preco_min' => 50, 'preco_max' => 70,
            'nota_min' => 4, 'ordem' => 'reputacao',
        ];
        $response = $this->get('/anuncios?'.http_build_query($filters))->assertOk();
        $paginator = $response->viewData('listings');
        $this->assertSame(13, $paginator->total());
        $this->assertCount(12, $paginator->items());
        parse_str(parse_url($paginator->nextPageUrl(), PHP_URL_QUERY), $query);
        foreach ($filters as $name => $value) {
            $this->assertEquals($value, $query[$name]);
        }
        $this->get($paginator->nextPageUrl())->assertOk()->assertViewHas('listings', fn ($items) => $items->count() === 1);
        $response->assertDontSee('Fora da faixa')->assertDontSee('Bloqueado')->assertDontSee('Sem reputação')
            ->assertSee('13 anúncio(s) encontrado(s)');
    }

    public function test_reputation_places_unrated_sellers_last_and_breaks_ties_by_id(): void
    {
        $unrated = $this->listing('Sem avaliações');
        $older = $this->listing('Mais antigo', 50, 2, 4);
        $newer = $this->listing('Mais novo', 50, 2, 4);
        $best = $this->listing('Melhor nota', 50, 1, 5);
        $response = $this->get('/anuncios?ordem=reputacao')->assertOk()->assertSee('Vendedor sem avaliações');
        $this->assertSame([$best->id, $newer->id, $older->id, $unrated->id], $response->viewData('listings')->pluck('id')->all());
        $this->get('/anuncios?nota_min=1')->assertOk()->assertDontSee('Vendedor sem avaliações');
    }

    public function test_prices_are_inclusive_and_sorting_is_stable(): void
    {
        $low = $this->listing('Menor', 10);
        $first = $this->listing('Empate antigo', 20);
        $second = $this->listing('Empate novo', 20);
        $this->listing('Maior', 30);
        foreach ([
            'menor_preco' => [$low->id, $second->id, $first->id],
            'maior_preco' => [$second->id, $first->id, $low->id],
        ] as $sort => $ids) {
            $response = $this->get('/anuncios?preco_min=10&preco_max=20&ordem='.$sort)->assertOk();
            $this->assertSame($ids, $response->viewData('listings')->pluck('id')->all());
        }
        $this->get('/anuncios?busca=inexistente')->assertOk()->assertSee('0 anúncio(s) encontrado(s)');
    }

    public function test_invalid_filters_are_rejected(): void
    {
        foreach ([
            ['preco_min' => 20, 'preco_max' => 10],
            ['nota_min' => 6], ['preco_min' => -1], ['page' => 0],
            ['ordem' => 'sql'], ['jogo' => 'abc'], ['busca' => ['array']],
        ] as $filters) {
            $this->getJson('/anuncios?'.http_build_query($filters))->assertUnprocessable();
        }
    }

    public function test_unpublished_and_ineligible_sellers_are_never_listed(): void
    {
        $this->listing('Visível');
        $this->listing('Rascunho')->update(['status' => 'rascunho']);
        $this->listing('Vendido')->update(['status' => 'vendido']);
        $this->listing('Bloqueado')->update(['status' => 'bloqueado']);
        $listing = $this->listing('Não aprovado');
        $listing->seller->sellerProfile->update(['status' => 'pending']);
        $this->get('/anuncios')->assertOk()->assertViewHas('listings', fn ($items) => $items->total() === 1);
    }
}
