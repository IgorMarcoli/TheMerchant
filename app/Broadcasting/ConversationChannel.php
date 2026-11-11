<?php

namespace App\Broadcasting;

use App\Models\Conversation;
use App\Models\User;

class ConversationChannel
{
    /**
     * Executado na assinatura e em cada reconexão do cliente: a mesma regra da
     * ConversationPolicy (participante com conta ativa; administrador não tem acesso pelo papel).
     */
    public function join(User $user, Conversation $conversation): bool
    {
        return $user->can('view', $conversation);
    }
}
