<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function purchase(?User $buyer = null, ?User $seller = null): OrderItem
    {
        $buyer ??= User::factory()->create();
        $seller ??= User::factory()->seller()->create();
        $slug = (string) Str::uuid();
        $game = Game::create(['name' => 'Game', 'slug' => $slug, 'active' => true]);
        $category = Category::create(['game_id' => $game->id, 'name' => 'Skins', 'slug' => $slug]);
        $listing = Listing::create([
            'seller_id' => $seller->id, 'game_id' => $game->id, 'category_id' => $category->id,
            'title' => 'Item', 'slug' => $slug, 'description' => 'Item de teste', 'price' => 50, 'status' => 'publicado',
        ]);
        $order = Order::create(['order_number' => $slug, 'buyer_id' => $buyer->id, 'total_amount' => 150, 'status' => 'pago']);

        return $order->items()->create([
            'listing_id' => $listing->id, 'seller_id' => $seller->id, 'unit_price' => 50,
            'quantity' => 3, 'delivery_status' => 'entregue', 'delivered_at' => now(),
        ]);
    }

    public function test_buyer_can_review_delivered_item_while_another_sellers_item_is_pending(): void
    {
        $item = $this->purchase();
        $other = $this->purchase($item->order->buyer);
        $other->update(['order_id' => $item->order_id, 'delivery_status' => 'em_entrega']);
        $this->actingAs($item->order->buyer)->post(route('orders.review.store', [$item->order, $item]), [
            'rating' => 5, 'buyer_id' => $other->seller_id, 'seller_id' => $other->seller_id,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', ['order_item_id' => $item->id, 'buyer_id' => $item->order->buyer_id,
            'seller_id' => $item->seller_id, 'rating' => 5, 'comment' => null]);
        $this->assertSame('pago', $item->order->fresh()->status);
        $this->assertDatabaseHas('seller_profiles', ['user_id' => $item->seller_id, 'reputation_score' => 5, 'total_reviews' => 1, 'total_sales' => 0]);
    }

    public function test_duplicate_submission_does_not_change_rating_or_reputation(): void
    {
        $item = $this->purchase();
        $url = route('orders.review.store', [$item->order, $item]);
        $this->actingAs($item->order->buyer)->post($url, ['rating' => 5])->assertSessionHasNoErrors();
        $this->post($url, ['rating' => 1])->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('seller_profiles', ['user_id' => $item->seller_id, 'reputation_score' => 5, 'total_reviews' => 1]);
    }

    public function test_reputation_reflects_all_persisted_reviews_without_changing_sales(): void
    {
        $item = $this->purchase();
        $item->seller->sellerProfile->update(['total_sales' => 17]);
        foreach ([5, 4, 1] as $rating) {
            $purchase = $this->purchase(null, $item->seller);
            app(ReviewService::class)->create($purchase->order->buyer, $purchase->order, $purchase, $rating);
        }
        $this->assertDatabaseHas('seller_profiles', ['user_id' => $item->seller_id, 'reputation_score' => 3.33, 'total_reviews' => 3, 'total_sales' => 17]);
    }

    public function test_mismatched_item_is_rejected_even_when_buyer_owns_both_orders(): void
    {
        $item = $this->purchase();
        $other = $this->purchase($item->order->buyer);
        $this->actingAs($item->order->buyer)->post(route('orders.review.store', [$item->order, $other]), ['rating' => 5])->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_item_from_another_buyers_order_cannot_be_reviewed_through_own_order(): void
    {
        $item = $this->purchase();
        $other = $this->purchase();
        $this->actingAs($item->order->buyer)->post(route('orders.review.store', [$item->order, $other]), ['rating' => 5])->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_other_users_including_admin_and_seller_cannot_review(): void
    {
        $item = $this->purchase();
        foreach ([User::factory()->create(), User::factory()->admin()->create(), $item->seller] as $user) {
            $this->actingAs($user)->post(route('orders.review.store', [$item->order, $item]), ['rating' => 5])->assertForbidden();
        }
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_unpaid_or_undelivered_items_are_rejected(): void
    {
        $item = $this->purchase();
        foreach ([['pendente', 'entregue'], ['cancelado', 'entregue'], ['pago', 'em_entrega'], ['concluido', 'em_entrega']] as [$status, $delivery]) {
            $item->order->update(['status' => $status]);
            $item->update(['delivery_status' => $delivery]);
            $this->actingAs($item->order->buyer)->post(route('orders.review.store', [$item->order, $item]), ['rating' => 5])->assertForbidden();
        }
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_invalid_rating_and_comment_are_rejected(): void
    {
        $item = $this->purchase();
        foreach ([[], ['rating' => 0], ['rating' => 6], ['rating' => 2.5], ['rating' => []], ['rating' => 5, 'comment' => str_repeat('a', 1001)]] as $payload) {
            $this->actingAs($item->order->buyer)->post(route('orders.review.store', [$item->order, $item]), $payload)->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_guest_and_suspended_buyer_cannot_review(): void
    {
        $item = $this->purchase();
        $url = route('orders.review.store', [$item->order, $item]);
        $this->post($url, ['rating' => 5])->assertRedirect(route('login'));
        $item->order->buyer->update(['status' => 'suspended']);
        $this->actingAs($item->order->buyer)->post($url, ['rating' => 5])->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_service_rechecks_persisted_state_instead_of_trusting_stale_models(): void
    {
        $item = $this->purchase();
        $order = $item->order;
        Order::whereKey($order->id)->update(['status' => 'cancelado']);
        $this->expectException(AuthorizationException::class);
        app(ReviewService::class)->create($order->buyer, $order, $item, 5);
    }

    public function test_service_also_rejects_mismatched_order_and_item(): void
    {
        $item = $this->purchase();
        $other = $this->purchase();
        $this->expectException(AuthorizationException::class);
        app(ReviewService::class)->create($item->order->buyer, $item->order, $other, 5);
    }

    public function test_database_enforces_one_review_per_item_regardless_of_quantity(): void
    {
        $item = $this->purchase();
        $review = app(ReviewService::class)->create($item->order->buyer, $item->order, $item, 5);
        $this->expectException(QueryException::class);
        Review::create($review->only(['order_id', 'order_item_id', 'buyer_id', 'seller_id', 'rating', 'comment']));
    }

    public function test_existing_order_form_uses_review_authorization(): void
    {
        $item = $this->purchase();
        $this->actingAs($item->order->buyer)->get(route('orders.show', $item->order))->assertOk()->assertSee('Avaliar Vendedor');
        $item->order->update(['status' => 'pendente']);
        $this->get(route('orders.show', $item->order))->assertOk()->assertDontSee('Avaliar Vendedor');
    }

    public function test_failure_updating_reputation_rolls_back_the_review(): void
    {
        $item = $this->purchase();
        SellerProfile::updating(function (): void {
            throw new RuntimeException('Falha simulada ao atualizar reputação');
        });
        try {
            app(ReviewService::class)->create($item->order->buyer, $item->order, $item, 5);
            $this->fail('A falha deveria interromper a transação.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha simulada ao atualizar reputação', $exception->getMessage());
        } finally {
            SellerProfile::flushEventListeners();
        }
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseHas('seller_profiles', ['user_id' => $item->seller_id, 'total_reviews' => 0]);
    }

    public function test_public_seller_reputation_updates_without_exposing_email(): void
    {
        $item = $this->purchase();
        $url = route('listings.show', $item->listing->slug);
        $this->get($url)->assertOk()->assertSee('Sem avaliações')->assertDontSee($item->seller->email);
        app(ReviewService::class)->create($item->order->buyer, $item->order, $item, 4);
        $this->get($url)->assertOk()->assertSee('4,00/5')->assertSee('1 avaliações')
            ->assertDontSee($item->seller->email)->assertDontSee($item->order->buyer->email);
    }

    public function test_async_review_returns_only_saved_rating_and_comment(): void
    {
        $item = $this->purchase();
        $this->actingAs($item->order->buyer)->postJson(route('orders.review.store', [$item->order, $item]), [
            'rating' => 4, 'comment' => 'Entrega rápida!',
        ])->assertCreated()->assertExactJson([
            'message' => 'Avaliação publicada com sucesso!',
            'review' => ['rating' => 4, 'comment' => 'Entrega rápida!'],
        ]);
        $this->get(route('orders.show', $item->order))->assertOk()
            ->assertSee('Você avaliou com 4 de 5 estrelas')->assertSee('Entrega rápida!')->assertDontSee('Avaliar Vendedor');
    }

    public function test_async_validation_errors_and_duplicate_are_returned_as_json(): void
    {
        $item = $this->purchase();
        $url = route('orders.review.store', [$item->order, $item]);
        $this->actingAs($item->order->buyer)->postJson($url, ['rating' => 6])->assertUnprocessable()->assertJsonValidationErrors('rating');
        $this->postJson($url, ['rating' => 5])->assertCreated();
        $this->postJson($url, ['rating' => 1])->assertUnprocessable()->assertJsonValidationErrors('rating');
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_saved_comments_are_escaped_and_forms_have_distinct_labels(): void
    {
        $item = $this->purchase();
        $other = $this->purchase($item->order->buyer);
        $other->update(['order_id' => $item->order_id]);
        $this->actingAs($item->order->buyer)->get(route('orders.show', $item->order))->assertOk()
            ->assertSee('id="review-comment-'.$item->id.'"', false)
            ->assertSee('id="review-comment-'.$other->id.'"', false)
            ->assertSee('aria-label="1 estrela"', false)->assertSee('aria-label="5 estrelas"', false);
        $comment = '<script>alert("test")</script>';
        app(ReviewService::class)->create($item->order->buyer, $item->order, $item, 5, $comment);
        $this->get(route('orders.show', $item->order))->assertOk()->assertSee($comment)->assertDontSee($comment, false);
    }
}
