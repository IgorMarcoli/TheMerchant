<?php

use App\Broadcasting\ConversationChannel;
use Illuminate\Support\Facades\Broadcast;

// Canal privado por conversa; nenhum conteúdo de chat é enviado a canais públicos.
Broadcast::channel('chat.{conversation}', ConversationChannel::class);
