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
        // A suspended seller may still fulfill existing purchases, but only for their own items.
        return $user->status === 'active' && (int) $user->id === (int) $item->seller_id;
    }
}
