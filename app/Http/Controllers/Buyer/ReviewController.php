<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\SellerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(Request $request, Order $order, OrderItem $item): RedirectResponse
    {
        // Validação: Somente compras concluídas podem ser avaliadas
        abort_unless($order->buyer_id === $request->user()->id, 403);
        abort_unless($order->status === 'concluido' || $item->delivery_status === 'entregue', 400, 'A avaliação só pode ser realizada após a conclusão do pedido.');

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // Evita avaliações duplicadas para o mesmo item
        if (Review::where('order_item_id', $item->id)->exists()) {
            return back()->with('error', 'Este item já foi avaliado anteriormente.');
        }

        DB::transaction(function () use ($order, $item, $request, $validated) {
            Review::create([
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'buyer_id' => $request->user()->id,
                'seller_id' => $item->seller_id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ]);

            // Atualização da reputação agregada do vendedor
            $sellerProfile = SellerProfile::where('user_id', $item->seller_id)->first();
            if ($sellerProfile) {
                $avgRating = Review::where('seller_id', $item->seller_id)->avg('rating');
                $countReviews = Review::where('seller_id', $item->seller_id)->count();

                $sellerProfile->update([
                    'reputation_score' => round($avgRating, 2),
                    'total_reviews' => $countReviews,
                ]);
            }
        });

        return back()->with('success', 'Avaliação enviada com sucesso! Obrigado pelo feedback.');
    }
}
