<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Exception;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $cart = Cart::with(['items.listing.primaryImage'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('warning', 'Seu carrinho está vazio para checkout.');
        }

        return view('checkout.index', compact('cart'));
    }

    public function process(CheckoutRequest $request): RedirectResponse
    {
        try {
            $result = $this->checkoutService->checkout($request->user(), $request->notes);

            return redirect($result['checkout_url']);
        } catch (Exception $e) {
            return back()->with('error', 'Falha ao processar checkout: ' . $e->getMessage());
        }
    }

    public function success(Order $order): View
    {
        return view('checkout.success', compact('order'));
    }

    public function cancel(Order $order): View
    {
        return view('checkout.cancel', compact('order'));
    }
}
