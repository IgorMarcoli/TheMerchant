<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListingDetailTest extends TestCase
{
    use RefreshDatabase;

    private function listing(string $status = 'publicado'): Listing
    {
        $seller = User::factory()->seller()->create();
        $seller->sellerProfile()->update([
            'reputation_score' => 4.75,
            'total_reviews' => 8,
            'total_sales' => 12,
        ]);
        $slug = (string) Str::uuid();
        $game = Game::create(['name' => 'Jogo de teste', 'slug' => $slug, 'active' => true]);
        $category = Category::create([
            'game_id' => $game->id,
            'name' => 'Cosméticos',
            'slug' => $slug,
            'type' => 'cosmetic',
        ]);

        return Listing::create([
            'seller_id' => $seller->id,
            'game_id' => $game->id,
            'category_id' => $category->id,
            'title' => 'Anúncio de teste',
            'slug' => $slug,
            'description' => 'Descrição completa para o anúncio de teste.',
            'price' => 75,
            'status' => $status,
        ]);
    }

    public function test_public_detail_shows_carousel_data_reputation_and_sales(): void
    {
        $listing = $this->listing();

        $this->get(route('listings.show', $listing->slug))
            ->assertOk()
            ->assertSee('Fotos do anúncio')
            ->assertSee('visualizações')
            ->assertSee('4,75/5')
            ->assertSee('12')
            ->assertSee('Adicionar ao carrinho');

        $this->assertDatabaseHas('listings', ['id' => $listing->id, 'views_count' => 1]);
    }

    public function test_unavailable_listing_is_visible_with_purchase_disabled(): void
    {
        $listing = $this->listing('vendido');

        $this->get(route('listings.show', $listing->slug))
            ->assertOk()
            ->assertSee('disabled', false)
            ->assertSee('Indisponível');
    }

    public function test_draft_and_blocked_listings_are_not_publicly_visible(): void
    {
        foreach (['rascunho', 'bloqueado'] as $status) {
            $listing = $this->listing($status);
            $this->get(route('listings.show', $listing->slug))->assertNotFound();
        }
    }

    public function test_guest_cannot_submit_listing_report(): void
    {
        $listing = $this->listing();

        $this->from(route('listings.show', $listing->slug))
            ->post(route('listings.report', $listing), [
            'reason' => 'fraude',
            'details' => 'Descrição detalhada da denúncia.',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_authenticated_buyer_can_submit_report_and_modal_is_available(): void
    {
        $listing = $this->listing();
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('listings.show', $listing->slug))
            ->assertOk()
            ->assertSee('Denunciar este anúncio')
            ->assertSee('role="dialog"', false);

        $this->post(route('listings.report', $listing), [
            'reason' => 'fraude',
            'details' => 'O anúncio não corresponde ao item entregue.',
        ])->assertRedirect(route('listings.show', $listing->slug))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $buyer->id,
            'listing_id' => $listing->id,
            'reason' => 'fraude',
            'details' => 'O anúncio não corresponde ao item entregue.',
            'status' => 'aberta',
        ]);
    }

    public function test_invalid_report_fields_are_rejected_without_persistence(): void
    {
        $listing = $this->listing();
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->from(route('listings.show', $listing->slug))
            ->post(route('listings.report', $listing), ['reason' => 'unexpected', 'details' => 'curto'])
            ->assertRedirect(route('listings.show', $listing->slug))
            ->assertSessionHasErrors(['reason', 'details']);

        $this->assertDatabaseCount('reports', 0);
    }
}
