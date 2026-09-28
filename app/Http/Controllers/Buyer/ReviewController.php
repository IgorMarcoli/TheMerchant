<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Order $order, OrderItem $item, ReviewService $reviews): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $review = $reviews->create($request->user(), $order, $item, (int) $data['rating'], $data['comment'] ?? null);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Avaliação publicada com sucesso!',
                'review' => $review->only(['rating', 'comment']),
            ], 201);
        }

        return back()->with('success', 'Avaliação enviada com sucesso! Obrigado pelo feedback.');
    }
}
