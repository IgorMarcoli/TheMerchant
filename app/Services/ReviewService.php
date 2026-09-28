<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function create(User $buyer, Order $order, OrderItem $item, int $rating, ?string $comment = null): Review
    {
        Validator::make(compact('rating', 'comment'), [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        return DB::transaction(function () use ($buyer, $order, $item, $rating, $comment): Review {
            // Reload authoritative state: callers may hold stale or modified models.
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $item = OrderItem::query()->findOrFail($item->id);
            Gate::forUser($buyer->fresh())->authorize('create', [Review::class, $order, $item]);

            // Serialize reviews of different items for the same seller before aggregating.
            $profile = SellerProfile::query()->where('user_id', $item->seller_id)->lockForUpdate()->firstOrFail();
            $item = OrderItem::query()->lockForUpdate()->findOrFail($item->id);
            Gate::forUser($buyer->fresh())->authorize('create', [Review::class, $order, $item]);

            if ($item->review()->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['rating' => 'Este item já foi avaliado anteriormente.']);
            }

            $review = Review::create([
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $item->seller_id,
                'rating' => $rating,
                'comment' => $comment,
            ]);

            // Locking reads see reviews committed while waiting for the profile lock.
            $ratings = Review::query()->where('seller_id', $item->seller_id)->lockForUpdate()->pluck('rating');
            $profile->update([
                'reputation_score' => round((float) $ratings->avg(), 2),
                'total_reviews' => $ratings->count(),
            ]);

            return $review;
        }, 5);
    }
}
