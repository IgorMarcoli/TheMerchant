<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatService
{
    public const PAGE_SIZE = 50;

    /**
     * Conversas do usuário, da atividade mais recente para a mais antiga.
     *
     * @return Collection<int, Conversation>
     */
    public function inbox(User $user): Collection
    {
        return Conversation::query()
            ->where(fn ($query) => $query->where('buyer_id', $user->id)->orWhere('seller_id', $user->id))
            ->with(['buyer', 'seller', 'listing.game', 'listing.category', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Abre a conversa única (comprador/interessado, vendedor, anúncio) ou a cria.
     * O remetente vem sempre da sessão; buyer_id só é aceito quando o vendedor retoma uma venda.
     */
    public function startOrGetConversation(User $user, int $listingId, ?int $buyerId = null): Conversation
    {
        $listing = Listing::with(['seller.sellerProfile', 'game', 'category'])->findOrFail($listingId);
        $actingAsSeller = $user->id === $listing->seller_id;

        if ($actingAsSeller && ! $buyerId) {
            throw ValidationException::withMessages([
                'buyer_id' => 'Informe o comprador para iniciar a conversa como vendedor.',
            ]);
        }

        $buyerId = $actingAsSeller ? $buyerId : $user->id;

        if ($buyerId === $listing->seller_id) {
            throw ValidationException::withMessages([
                'conversation' => 'Não é permitido iniciar conversa consigo mesmo.',
            ]);
        }

        $participants = [
            'buyer_id' => $buyerId,
            'seller_id' => $listing->seller_id,
            'listing_id' => $listing->id,
        ];

        if (! Conversation::where($participants)->exists()) {
            $this->ensureCanOpen($listing, $buyerId, $actingAsSeller);
        }

        return DB::transaction(function () use ($participants): Conversation {
            // firstOrCreate usa createOrFirst: a constraint única resolve cliques simultâneos.
            $conversation = Conversation::firstOrCreate($participants);

            return $conversation->load(['buyer', 'seller', 'listing.game', 'listing.category']);
        });
    }

    /**
     * Grava a mensagem de forma idempotente por (conversa, remetente, client_uuid) e
     * só transmite o sinal depois do commit.
     */
    public function sendMessage(User $user, Conversation $conversation, string $body, string $clientUuid): Message
    {
        $body = trim($body);

        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => 'A mensagem não pode ser vazia.',
            ]);
        }

        return DB::transaction(function () use ($user, $conversation, $body, $clientUuid): Message {
            $message = $conversation->messages()->firstOrCreate(
                ['sender_id' => $user->id, 'client_uuid' => $clientUuid],
                ['body' => $body],
            );

            if ($message->wasRecentlyCreated) {
                $this->advanceReadCursor($conversation, $user, $message->id);
                $conversation->touch();

                // MessageSent implementa ShouldDispatchAfterCommit: rollback não transmite nada.
                MessageSent::dispatch($message);
            }

            return $message->load('sender');
        });
    }

    /**
     * Página do histórico em ordem cronológica.
     * after_id recupera o que chegou depois (reconexão); before_id carrega mensagens antigas.
     *
     * @return array{messages: Collection<int, Message>, has_more: bool}
     */
    public function history(Conversation $conversation, ?int $afterId = null, ?int $beforeId = null): array
    {
        $query = $conversation->messages()->with('sender:id,name');

        if ($afterId !== null) {
            $page = $query->where('id', '>', $afterId)->orderBy('id')->limit(self::PAGE_SIZE + 1)->get();

            return [
                'messages' => $page->take(self::PAGE_SIZE)->values(),
                'has_more' => $page->count() > self::PAGE_SIZE,
            ];
        }

        if ($beforeId !== null) {
            $query->where('id', '<', $beforeId);
        }

        $page = $query->orderByDesc('id')->limit(self::PAGE_SIZE + 1)->get();

        return [
            'messages' => $page->take(self::PAGE_SIZE)->reverse()->values(),
            'has_more' => $page->count() > self::PAGE_SIZE,
        ];
    }

    /**
     * Avança o cursor de leitura do participante; nunca retrocede.
     */
    public function markAsRead(User $user, Conversation $conversation, int $messageId): void
    {
        if (! $conversation->messages()->whereKey($messageId)->exists()) {
            throw ValidationException::withMessages([
                'last_read_message_id' => 'A mensagem informada não pertence a esta conversa.',
            ]);
        }

        $this->advanceReadCursor($conversation, $user, $messageId);
    }

    /**
     * Total de mensagens recebidas e não lidas em todas as conversas do usuário.
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

    private function ensureCanOpen(Listing $listing, int $buyerId, bool $actingAsSeller): void
    {
        $purchased = OrderItem::where('listing_id', $listing->id)
            ->whereHas('order', fn ($order) => $order->where('buyer_id', $buyerId)->whereIn('status', Order::PAID_STATUSES))
            ->exists();

        if ($actingAsSeller && ! $purchased) {
            throw ValidationException::withMessages([
                'conversation' => 'Não há compra deste comprador para este anúncio.',
            ]);
        }

        // Interessados só abrem conversa nova em anúncio publicado; quem comprou retoma a qualquer momento.
        if (! $actingAsSeller && ! $purchased && ! $listing->isAvailable()) {
            throw ValidationException::withMessages([
                'conversation' => 'Este anúncio não está mais disponível para novas conversas.',
            ]);
        }
    }

    private function advanceReadCursor(Conversation $conversation, User $user, int $messageId): void
    {
        $column = match ($user->id) {
            $conversation->buyer_id => 'buyer_last_read_message_id',
            $conversation->seller_id => 'seller_last_read_message_id',
            default => null,
        };

        if ($column === null) {
            return;
        }

        // Atualização condicional e atômica: leituras concorrentes não fazem o cursor voltar.
        // toBase() evita alterar updated_at, que ordena a caixa de entrada por atividade.
        Conversation::whereKey($conversation->id)
            ->where(fn ($query) => $query->whereNull($column)->orWhere($column, '<', $messageId))
            ->toBase()
            ->update([$column => $messageId]);

        $conversation->setAttribute($column, max((int) $conversation->getAttribute($column), $messageId));
        $conversation->syncOriginalAttribute($column);
    }
}
