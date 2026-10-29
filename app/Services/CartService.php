<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CartService
{
    public function forUser(User $user): Cart
    {
        return Cart::with([
            'items.listing.primaryImage',
            'items.listing.seller',
            'items.listing.category',
            'items.listing.game',
        ])->firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * @return array{redirect: string, flash_type: string, message: string}
     */
    public function add(User $user, Listing $listing): array
    {
        $listing->loadMissing(['game', 'category', 'seller.sellerProfile']);

        if (! $listing->isAvailable()) {
            return [
                'redirect' => 'back',
                'flash_type' => 'error',
                'message' => 'Este anúncio não está mais disponível.',
            ];
        }

        if ((int) $listing->seller_id === (int) $user->id) {
            return [
                'redirect' => 'back',
                'flash_type' => 'error',
                'message' => 'Você não pode comprar seu próprio produto.',
            ];
        }

        return DB::transaction(function () use ($user, $listing): array {
            $lockedListing = Listing::query()
                ->with(['game', 'category', 'seller.sellerProfile'])
                ->lockForUpdate()
                ->findOrFail($listing->id);

            if (! $lockedListing->isAvailable()) {
                return [
                    'redirect' => 'back',
                    'flash_type' => 'error',
                    'message' => 'Este anúncio não está mais disponível.',
                ];
            }

            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            $existing = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('listing_id', $lockedListing->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return [
                    'redirect' => 'back',
                    'flash_type' => 'info',
                    'message' => 'O item já está presente no seu carrinho.',
                ];
            }

            CartItem::create([
                'cart_id' => $cart->id,
                'listing_id' => $lockedListing->id,
                'quantity' => 1,
                'unit_price' => $lockedListing->price,
            ]);

            return [
                'redirect' => 'cart.index',
                'flash_type' => 'success',
                'message' => 'Item adicionado ao carrinho com sucesso!',
            ];
        });
    }

    /**
     * @return array{flash_type: string, message: string}
     */
    public function updateQuantity(User $user, CartItem $item, int $quantity): array
    {
        return DB::transaction(function () use ($user, $item, $quantity): array {
            $lockedItem = CartItem::query()
                ->with(['cart', 'listing.category', 'listing.game', 'listing.seller.sellerProfile'])
                ->lockForUpdate()
                ->findOrFail($item->id);

            Gate::forUser($user)->authorize('update', $lockedItem);

            $listing = $lockedItem->listing;

            if ($listing === null || $listing->category?->type !== 'service') {
                return [
                    'flash_type' => 'error',
                    'message' => 'A quantidade de cosméticos não pode ser alterada.',
                ];
            }

            if (! $listing->isAvailable()) {
                return [
                    'flash_type' => 'error',
                    'message' => 'Este anúncio não está mais disponível.',
                ];
            }

            if ($quantity > $listing->capacity) {
                return [
                    'flash_type' => 'error',
                    'message' => "A quantidade selecionada excede a capacidade disponível ({$listing->capacity} sessão(ões)).",
                ];
            }

            $lockedItem->update([
                'quantity' => $quantity,
                'unit_price' => $listing->price,
            ]);

            return [
                'flash_type' => 'success',
                'message' => 'Quantidade de sessões atualizada.',
            ];
        });
    }

    public function remove(User $user, CartItem $item): void
    {
        DB::transaction(function () use ($user, $item): void {
            $lockedItem = CartItem::query()->with('cart')->lockForUpdate()->findOrFail($item->id);
            Gate::forUser($user)->authorize('delete', $lockedItem);
            $lockedItem->delete();
        });
    }

    public function clear(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $cart = Cart::where('user_id', $user->id)->first();
            if ($cart !== null) {
                $cart->items()->delete();
            }
        });
    }
}
