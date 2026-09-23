<?php

namespace App\Policies;

use App\Models\OrderItem;
use App\Models\User;

class OrderItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->status === 'active' && ($user->sellerProfile()->exists() || $user->isAdmin());
    }

    public function deliver(User $user, OrderItem $item): bool
    {
        // Selling suspension must not prevent fulfilling existing purchases.
        return $user->status === 'active' && ($user->id === $item->seller_id || $user->isAdmin());
    }
}
