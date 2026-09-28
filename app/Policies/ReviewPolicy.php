<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

class ReviewPolicy
{
    public function create(User $user, Order $order, OrderItem $item): bool
    {
        return $user->isActive()
            && (int) $order->buyer_id === (int) $user->id
            && (int) $item->order_id === (int) $order->id
            && $order->isPaid()
            && $item->delivery_status === 'entregue';
    }
}
