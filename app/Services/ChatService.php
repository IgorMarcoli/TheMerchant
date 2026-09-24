<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatService
{
    /**
     * Start or retrieve an existing conversation with transactional integrity.
     */
    public function startOrGetConversation(User $user, int $listingId, ?int $buyerId = null): Conversation
    {
        $listing = Listing::findOrFail($listingId);

        if ($user->id === $listing->seller_id) {
            // User is the seller
            if (! $buyerId) {
                throw ValidationException::withMessages([
                    'buyer_id' => 'Informe o comprador para iniciar a conversa como vendedor.',
                ]);
            }

            if ($buyerId === $user->id) {
                throw ValidationException::withMessages([
                    'conversation' => 'Não é permitido iniciar conversa consigo mesmo.',
                ]);
            }

            // Must have existing sale item or existing conversation
            $hasSale = OrderItem::where('listing_id', $listing->id)
                ->whereHas('order', fn ($q) => $q->where('buyer_id', $buyerId))
                ->exists();

            $hasConversation = Conversation::where('listing_id', $listing->id)
                ->where('buyer_id', $buyerId)
                ->where('seller_id', $user->id)
                ->exists();

            if (! $hasSale && ! $hasConversation) {
                throw ValidationException::withMessages([
                    'conversation' => 'Não há pedido registrado deste comprador para este anúncio.',
                ]);
            }

            $finalBuyerId = $buyerId;
            $finalSellerId = $user->id;
        } else {
            // User is the buyer
            if ($user->id === $listing->seller_id) {
                throw ValidationException::withMessages([
                    'conversation' => 'Não é permitido iniciar conversa em seu próprio anúncio.',
                ]);
            }

            $hasOrder = OrderItem::where('listing_id', $listing->id)
                ->whereHas('order', fn ($q) => $q->where('buyer_id', $user->id))
                ->exists();

            $hasConversation = Conversation::where('listing_id', $listing->id)
                ->where('buyer_id', $user->id)
                ->where('seller_id', $listing->seller_id)
                ->exists();

            if ($listing->status !== 'publicado' && ! $hasOrder && ! $hasConversation) {
                throw ValidationException::withMessages([
                    'conversation' => 'Este anúncio não está mais disponível para novas conversas.',
                ]);
            }

            $finalBuyerId = $user->id;
            $finalSellerId = $listing->seller_id;
        }

        return DB::transaction(function () use ($finalBuyerId, $finalSellerId, $listing) {
            $conversation = Conversation::firstOrCreate([
                'buyer_id' => $finalBuyerId,
                'seller_id' => $finalSellerId,
                'listing_id' => $listing->id,
            ]);

            $conversation->touch();

            return $conversation->load(['buyer', 'seller', 'listing.game', 'listing.category']);
        });
    }

    /**
     * Send a message idempotently using client_uuid.
     */
    public function sendMessage(User $user, Conversation $conversation, string $body, string $clientUuid): Message
    {
        $trimmedBody = trim($body);

        if (empty($trimmedBody)) {
            throw ValidationException::withMessages([
                'body' => 'A mensagem não pode ser vazia.',
            ]);
        }

        // Idempotency check: if already recorded with same client_uuid, return immediately
        $existing = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $user->id)
            ->where('client_uuid', $clientUuid)
            ->first();

        if ($existing) {
            return $existing->load('sender');
        }

        return DB::transaction(function () use ($user, $conversation, $trimmedBody, $clientUuid) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'client_uuid' => $clientUuid,
                'body' => $trimmedBody,
            ]);

            // Sender has read their own message
            if ($user->id === $conversation->buyer_id) {
                $conversation->buyer_last_read_message_id = $message->id;
            } elseif ($user->id === $conversation->seller_id) {
                $conversation->seller_last_read_message_id = $message->id;
            }

            $conversation->save();

            $message->load('sender');

            // Dispatch broadcast event
            MessageSent::dispatch($message);

            return $message;
        });
    }

    /**
     * Mark conversation messages as read up to messageId.
     */
    public function markAsRead(User $user, Conversation $conversation, int $messageId): void
    {
        $message = $conversation->messages()->where('id', $messageId)->first();

        if (! $message) {
            throw ValidationException::withMessages([
                'last_read_message_id' => 'A mensagem informada não pertence a esta conversa.',
            ]);
        }

        DB::transaction(function () use ($user, $conversation, $messageId) {
            if ($user->id === $conversation->buyer_id) {
                if (is_null($conversation->buyer_last_read_message_id) || $messageId > $conversation->buyer_last_read_message_id) {
                    $conversation->update(['buyer_last_read_message_id' => $messageId]);
                }
            } elseif ($user->id === $conversation->seller_id) {
                if (is_null($conversation->seller_last_read_message_id) || $messageId > $conversation->seller_last_read_message_id) {
                    $conversation->update(['seller_last_read_message_id' => $messageId]);
                }
            }
        });
    }

    /**
     * Compute total unread messages across all active user conversations.
     */
    public function getUnreadCountForUser(User $user): int
    {
        $asBuyer = Message::whereHas('conversation', function ($q) use ($user) {
            $q->where('buyer_id', $user->id)
                ->where(function ($sub) {
                    $sub->whereNull('conversations.buyer_last_read_message_id')
                        ->orWhereColumn('messages.id', '>', 'conversations.buyer_last_read_message_id');
                });
        })->where('sender_id', '!=', $user->id)->count();

        $asSeller = Message::whereHas('conversation', function ($q) use ($user) {
            $q->where('seller_id', $user->id)
                ->where(function ($sub) {
                    $sub->whereNull('conversations.seller_last_read_message_id')
                        ->orWhereColumn('messages.id', '>', 'conversations.seller_last_read_message_id');
                });
        })->where('sender_id', '!=', $user->id)->count();

        return $asBuyer + $asSeller;
    }
}
