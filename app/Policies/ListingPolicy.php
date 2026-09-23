<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    /**
     * Vendedor dono do anúncio ou administrador podem atualizar.
     */
    public function update(User $user, Listing $listing): bool
    {
        return $user->isAdmin() || ($user->isSeller() && $user->id === $listing->seller_id && $listing->status !== 'bloqueado');
    }

    /**
     * Vendedor dono do anúncio ou administrador podem excluir.
     */
    public function delete(User $user, Listing $listing): bool
    {
        return $user->isAdmin() || ($user->isSeller() && $user->id === $listing->seller_id && $listing->status !== 'bloqueado');
    }

    /**
     * Somente usuários com permissão de vendedor podem criar anúncios.
     */
    public function viewAny(User $user): bool
    {
        return $user->status === 'active' && ($user->sellerProfile()->exists() || $user->isAdmin());
    }

    public function create(User $user): bool
    {
        return $user->isSeller();
    }
}
