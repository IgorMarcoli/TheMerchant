<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::with(['items.listing.primaryImage', 'payment'])
            ->where('buyer_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless(
            $order->buyer_id === $request->user()->id || $request->user()->isAdmin(),
            403,
            'Você não tem autorização para visualizar este pedido.'
        );

        $order->load(['items.listing.primaryImage', 'items.seller', 'items.review', 'payment']);

        return view('orders.show', compact('order'));
    }
}
