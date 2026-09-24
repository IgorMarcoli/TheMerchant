<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarkAsReadRequest;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\StartChatRequest;
use App\Models\Conversation;
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
    ) {}

    /**
     * Display chat inbox and responsive conversation view.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $conversations = Conversation::where('buyer_id', $user->id)
            ->orWhere('seller_id', $user->id)
            ->with(['buyer', 'seller', 'listing.game', 'listing.category', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->get();

        $activeConversation = null;
        $activeMessages = collect();

        $requestedId = $request->query('c');

        if ($requestedId) {
            $candidate = $conversations->firstWhere('id', (int) $requestedId);
            if ($candidate && $user->can('view', $candidate)) {
                $activeConversation = $candidate;
            }
        }

        if (! $activeConversation && $conversations->isNotEmpty()) {
            $activeConversation = $conversations->first();
        }

        if ($activeConversation) {
            $activeMessages = $activeConversation->messages()
                ->with('sender')
                ->orderBy('id', 'asc')
                ->take(100)
                ->get();
        }

        return view('chat.index', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messages' => $activeMessages,
            'unreadCount' => $this->chatService->getUnreadCountForUser($user),
        ]);
    }

    /**
     * Start or open a conversation from listing/order/sale.
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

    /**
     * Display a specific conversation or redirect to inbox with it selected.
     */
    public function show(Request $request, Conversation $conversation): View|RedirectResponse|JsonResponse
    {
        $this->authorize('view', $conversation);

        if ($request->wantsJson()) {
            return response()->json([
                'conversation' => [
                    'id' => $conversation->id,
                    'listing' => [
                        'id' => $conversation->listing->id,
                        'title' => $conversation->listing->title,
                        'price' => (float) $conversation->listing->price,
                    ],
                ],
                'messages' => $conversation->messages()->with('sender')->orderBy('id', 'asc')->get()->map->toSafeArray(),
            ]);
        }

        return redirect()->route('chat.index', ['c' => $conversation->id]);
    }

    /**
     * Incremental/polling fetch of messages for a conversation.
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $query = $conversation->messages()->with('sender')->orderBy('id', 'asc');

        if ($request->filled('after_id')) {
            $query->where('id', '>', $request->integer('after_id'));
        }

        $messages = $query->take(100)->get();

        return response()->json([
            'messages' => $messages->map->toSafeArray(),
        ]);
    }

    /**
     * Store and broadcast a newly composed message.
     */
    public function store(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $message = $this->chatService->sendMessage(
            $request->user(),
            $conversation,
            $request->string('body')->value(),
            $request->string('client_uuid')->value()
        );

        return response()->json([
            'message' => $message->toSafeArray(),
        ], 201);
    }

    /**
     * Advance conversation read cursor for current user.
     */
    public function markAsRead(MarkAsReadRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('markAsRead', $conversation);

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

    /**
     * Get unread badge count for navbar.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->chatService->getUnreadCountForUser($request->user()),
        ]);
    }
}

