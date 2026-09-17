<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * O comprador que criou o pedido, os vendedores envolvidos ou administradores podem visualizar.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin() || $user->id === $order->buyer_id) {
            return true;
        }

        // Verifica se o usuário é vendedor de algum dos itens deste pedido
        return $order->items()->where('seller_id', $user->id)->exists();
    }
}
