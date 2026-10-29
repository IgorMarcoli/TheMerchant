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
        return $listing->archived_at === null
            && in_array($listing->status, ['rascunho', 'publicado', 'pausado'], true)
            && ($user->isAdmin() || ($user->isSeller() && $user->id === $listing->seller_id));
    }

    /**
     * Vendedor dono do anúncio ou administrador podem excluir.
     */
    public function delete(User $user, Listing $listing): bool
    {
        return $listing->archived_at === null
            && ($user->isAdmin() || ($user->isSeller() && $user->id === $listing->seller_id && $listing->status !== 'bloqueado'));
    }

    public function changeStatus(User $user, Listing $listing): bool
    {
        return $this->update($user, $listing) && in_array($listing->status, ['publicado', 'pausado'], true);
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
