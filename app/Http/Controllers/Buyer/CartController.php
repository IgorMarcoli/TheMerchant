<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Listing;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {
    }

    public function index(Request $request): View
    {
        $cart = $this->cartService->forUser($request->user());

        return view('cart.index', compact('cart'));
    }

    public function add(Request $request, Listing $listing): RedirectResponse
    {
        $result = $this->cartService->add($request->user(), $listing);

        if ($result['redirect'] === 'cart.index') {
            return redirect()->route('cart.index')->with($result['flash_type'], $result['message']);
        }

        return back()->with($result['flash_type'], $result['message']);
    }

    public function update(UpdateCartItemRequest $request, CartItem $item): RedirectResponse
    {
        $result = $this->cartService->updateQuantity(
            $request->user(),
            $item,
            (int) $request->validated('quantity')
        );

        return back()->with($result['flash_type'], $result['message']);
    }

    public function remove(Request $request, CartItem $item): RedirectResponse
    {
        $this->authorize('delete', $item);
        $this->cartService->remove($request->user(), $item);

        return back()->with('success', 'Item removido do carrinho.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $this->cartService->clear($request->user());

        return back()->with('info', 'Carrinho esvaziado.');
    }
}
