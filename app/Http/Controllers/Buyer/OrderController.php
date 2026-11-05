<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::with(['items.listing.primaryImage', 'payment'])
            ->where('buyer_id', auth()->id())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load(['items.listing.primaryImage', 'items.seller', 'items.review', 'payment']);

        return view('orders.show', compact('order'));
    }
}
