<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Sinal de nova mensagem (RF22). O WebSocket nunca transporta o texto: o cliente
 * busca o conteúdo pelo HTTP, que revalida sessão, conta ativa e participação a
 * cada leitura. Assim, uma conexão já aberta por conta suspensa não recebe nada útil.
 */
class MessageSent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;

    public int $conversationId;

    public int $messageId;

    public int $senderId;

    public function __construct(Message $message)
    {
        $this->conversationId = $message->conversation_id;
        $this->messageId = $message->id;
        $this->senderId = $message->sender_id;
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.'.$this->conversationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array{conversation_id: int, message_id: int, sender_id: int}
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'sender_id' => $this->senderId,
        ];
    }
}
