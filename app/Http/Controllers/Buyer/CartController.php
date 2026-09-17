<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = Cart::with(['items.listing.primaryImage', 'items.listing.seller'])
            ->firstOrCreate(['user_id' => $request->user()->id]);

        return view('cart.index', compact('cart'));
    }

    public function add(Request $request, Listing $listing): RedirectResponse
    {
        if (!$listing->isAvailable()) {
            return back()->with('error', 'Este anúncio não está mais disponível.');
        }

        if ($listing->seller_id === $request->user()->id) {
            return back()->with('error', 'Você não pode comprar seu próprio produto.');
        }

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        $item = CartItem::where('cart_id', $cart->id)
            ->where('listing_id', $listing->id)
            ->first();

        if ($item) {
            return back()->with('info', 'O item já está presente no seu carrinho.');
        }

        CartItem::create([
            'cart_id'    => $cart->id,
            'listing_id' => $listing->id,
            'quantity'   => 1,
            'unit_price' => $listing->price,
        ]);

        return redirect()->route('cart.index')->with('success', 'Item adicionado ao carrinho com sucesso!');
    }

    public function remove(CartItem $item): RedirectResponse
    {
        $item->delete();
        return back()->with('success', 'Item removido do carrinho.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();
        if ($cart) {
            $cart->items()->delete();
        }

        return back()->with('info', 'Carrinho esvaziado.');
    }
}
