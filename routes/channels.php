<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{conversationId}', function (User $user, int|string $conversationId) {
    if (! $user->isActive()) {
        return false;
    }

    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    return $conversation->isParticipant($user);
});
