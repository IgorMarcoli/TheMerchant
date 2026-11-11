<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatHistoryRequest;
use App\Http\Requests\MarkAsReadRequest;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\StartChatRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ChatService $chatService
    ) {
    }

    /**
     * Caixa de entrada com a conversa selecionada e sua página mais recente.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $this->authorize('viewAny', Conversation::class);

        $conversations = $this->chatService->inbox($user);
        $activeConversation = $conversations->firstWhere('id', $request->integer('c')) ?? $conversations->first();
        $history = $activeConversation
            ? $this->chatService->history($activeConversation)
            : ['messages' => collect(), 'has_more' => false];

        return view('chat.index', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messages' => $history['messages'],
            'hasOlderMessages' => $history['has_more'],
            'unreadCount' => $this->chatService->getUnreadCountForUser($user),
            'realtime' => $this->realtimeClientConfig(),
        ]);
    }

    /**
     * Abre a conversa a partir do anúncio, do pedido ou da venda.
     */
    public function start(StartChatRequest $request): RedirectResponse
    {
        $conversation = $this->chatService->startOrGetConversation(
            $request->user(),
            $request->integer('listing_id'),
            $request->integer('buyer_id') ?: null
        );

        return redirect()->route('chat.index', ['c' => $conversation->id]);
    }

    public function show(ChatHistoryRequest $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        if (! $request->wantsJson()) {
            return redirect()->route('chat.index', ['c' => $conversation->id]);
        }

        $history = $this->chatService->history($conversation);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'listing' => [
                    'id' => $conversation->listing->id,
                    'title' => $conversation->listing->title,
                    'price' => (float) $conversation->listing->price,
                ],
            ],
            'messages' => $history['messages']->map(fn (Message $message) => $message->toSafeArray()),
            'has_more' => $history['has_more'],
        ]);
    }

    /**
     * Histórico paginado: after_id (sincronização/reconexão) ou before_id (mensagens antigas).
     */
    public function messages(ChatHistoryRequest $request, Conversation $conversation): JsonResponse
    {
        $history = $this->chatService->history($conversation, $request->afterId(), $request->beforeId());

        return response()->json([
            'messages' => $history['messages']->map(fn (Message $message) => $message->toSafeArray()),
            'has_more' => $history['has_more'],
        ]);
    }

    public function store(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $message = $this->chatService->sendMessage(
            $request->user(),
            $conversation,
            $request->string('body')->value(),
            $request->string('client_uuid')->value()
        );

        return response()->json([
            'message' => $message->toSafeArray(),
        ], $message->wasRecentlyCreated ? 201 : 200);
    }

    public function markAsRead(MarkAsReadRequest $request, Conversation $conversation): JsonResponse
    {
        $this->chatService->markAsRead(
            $request->user(),
            $conversation,
            $request->integer('last_read_message_id')
        );

        return response()->json([
            'status' => 'ok',
            'unread_count' => $this->chatService->getUnreadCountForUser($request->user()),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->chatService->getUnreadCountForUser($request->user()),
        ]);
    }

    /**
     * Dados públicos do Reverb para o Echo; o segredo da aplicação nunca vai ao navegador.
     *
     * @return array{key: string, host: string, port: int, scheme: string}|null
     */
    private function realtimeClientConfig(): ?array
    {
        $connection = config('broadcasting.connections.reverb');

        if (config('broadcasting.default') !== 'reverb' || empty($connection['key']) || empty($connection['options']['host'])) {
            return null;
        }

        return [
            'key' => (string) $connection['key'],
            'host' => (string) $connection['options']['host'],
            'port' => (int) ($connection['options']['port'] ?? 443),
            'scheme' => (string) ($connection['options']['scheme'] ?? 'https'),
        ];
    }
}
