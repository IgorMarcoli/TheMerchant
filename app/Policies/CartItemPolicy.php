<?php

namespace App\Policies;

use App\Models\CartItem;
use App\Models\User;

class CartItemPolicy
{
    public function update(User $user, CartItem $item): bool
    {
        return $this->ownsCart($user, $item);
    }

    public function delete(User $user, CartItem $item): bool
    {
        return $this->ownsCart($user, $item);
    }

    private function ownsCart(User $user, CartItem $item): bool
    {
        if ($item->relationLoaded('cart') && $item->cart !== null) {
            return (int) $item->cart->user_id === (int) $user->id;
        }

        return $item->cart()->where('user_id', $user->id)->exists();
    }
}
