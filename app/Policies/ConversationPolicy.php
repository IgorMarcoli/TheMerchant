<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Determine whether the user can view any conversations.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Determine whether the user can view the conversation.
     * Note: Admins do NOT obtain access to private conversations merely by role.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $user->isActive() && $conversation->isParticipant($user);
    }

    /**
     * Determine whether the user can send a message in the conversation.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $user->isActive()
            && $conversation->isParticipant($user)
            && $conversation->buyer_id !== $conversation->seller_id;
    }

    /**
     * Determine whether the user can mark messages in the conversation as read.
     */
    public function markAsRead(User $user, Conversation $conversation): bool
    {
        return $user->isActive() && $conversation->isParticipant($user);
    }
}
