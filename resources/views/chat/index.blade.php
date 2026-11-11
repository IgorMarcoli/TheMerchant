@extends('layouts.app')

@section('title', 'Caixa de Entrada e Mensagens')

@section('content')
<div class="max-w-6xl mx-auto" x-data="chatInbox({
    authId: {{ auth()->id() }},
    authName: {{ Js::from(auth()->user()->name) }},
    activeConversationId: {{ $activeConversation ? $activeConversation->id : 'null' }},
    initialMessages: {{ Js::from($messages->map->toSafeArray()) }},
    hasOlder: {{ Js::from($hasOlderMessages) }},
    realtime: {{ Js::from($realtime) }},
    csrfToken: '{{ csrf_token() }}',
    messagesUrl: '{{ $activeConversation ? route('chat.messages', $activeConversation) : '' }}',
    sendUrl: '{{ $activeConversation ? route('chat.messages.store', $activeConversation) : '' }}',
    readUrl: '{{ $activeConversation ? route('chat.read', $activeConversation) : '' }}'
})">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <x-page-heading title="Mensagens & Negociações" eyebrow="DE PLAYER PARA PLAYER" description="Converse com compradores e vendedores e acompanhe suas negociações." />
        </div>

        <div class="flex items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-slate-800 bg-slate-900/90 text-slate-300">
                <span class="w-2 h-2 rounded-full" :class="connectionStatus === 'connected' ? 'bg-emerald-400' : (connectionStatus === 'reconnecting' ? 'bg-amber-400 animate-pulse' : (connectionStatus === 'revoked' ? 'bg-rose-500' : 'bg-slate-500'))"></span>
                <span x-text="connectionStatusText"></span>
            </span>
        </div>
    </div>

    <!-- Região acessível para leitores de tela -->
    <div aria-live="polite" class="sr-only" x-text="screenReaderAnnouncement"></div>

    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl min-h-[620px] grid grid-cols-1 lg:grid-cols-12">
        <!-- Lista de Conversas (Esquerda) -->
        <div class="lg:col-span-4 border-r border-slate-800 flex flex-col bg-slate-950/40"
             :class="{ 'hidden lg:flex': mobileShowChat && activeConversationId, 'flex': !mobileShowChat || !activeConversationId }">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/60">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Conversas Recentes</span>
                <span class="text-[11px] font-semibold text-brand-400 bg-brand-950/60 px-2 py-0.5 rounded-full border border-brand-800/40">
                    {{ $conversations->count() }}
                </span>
            </div>

            <div class="overflow-y-auto flex-1 divide-y divide-slate-800/60">
                @forelse($conversations as $conversation)
                    @php
                        $other = $conversation->getOtherParticipant(auth()->user());
                        $isActive = $activeConversation && $activeConversation->id === $conversation->id;
                        $unreadInConv = $conversation->unreadCountFor(auth()->user());
                    @endphp
                    <a href="{{ route('chat.index', ['c' => $conversation->id]) }}"
                       class="block p-4 transition text-left focus:outline-none focus:ring-2 focus:ring-brand-500 {{ $isActive ? 'bg-slate-800/80 border-l-4 border-brand-500' : 'hover:bg-slate-800/40' }}"
                       @click="mobileShowChat = true">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <h2 class="text-xs font-bold text-white truncate">
                                {{ $other?->name ?? 'Usuário' }}
                            </h2>
                            <span class="text-[10px] text-slate-500 whitespace-nowrap">
                                {{ $conversation->latestMessage?->created_at?->format('d/m H:i') ?? $conversation->updated_at->format('d/m') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5 text-[11px] text-brand-400 font-medium mb-1 truncate">
                            <span aria-hidden="true">🎮</span>
                            <span class="truncate">{{ $conversation->listing->title }}</span>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <p class="text-slate-400 text-[11px] truncate max-w-[200px]">
                                {{ $conversation->latestMessage?->body ?? 'Nenhuma mensagem ainda' }}
                            </p>
                            @if($unreadInConv > 0)
                                <span class="px-1.5 py-0.5 rounded-full bg-brand-500 text-slate-950 font-black text-[10px] shrink-0">
                                    {{ $unreadInConv }}
                                </span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center text-slate-500 text-xs">
                        <span aria-hidden="true" class="text-3xl block mb-2">📭</span>
                        Nenhuma conversa iniciada.<br>
                        Acesse o catálogo e converse com um vendedor.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Área de Conversa Ativa (Direita) -->
        <div class="lg:col-span-8 flex flex-col bg-slate-900/30"
             :class="{ 'flex': mobileShowChat || !activeConversationId, 'hidden lg:flex': !mobileShowChat }">
            @if($activeConversation)
                @php
                    $activeOther = $activeConversation->getOtherParticipant(auth()->user());
                @endphp
                <!-- Topo da Conversa / Contexto do Anúncio (RF08, RF21) -->
                <div class="p-4 border-b border-slate-800 bg-slate-900/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="mobileShowChat = false" class="lg:hidden p-1.5 rounded-lg bg-slate-800 text-slate-300 hover:text-white focus:outline-none focus:ring-2 focus:ring-brand-500" aria-label="Voltar para a lista de conversas">
                            &larr;
                        </button>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-slate-950 font-black text-sm shrink-0">
                            {{ mb_substr($activeOther?->name ?? 'U', 0, 1) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-bold text-white">{{ $activeOther?->name ?? 'Usuário' }}</h2>
                                <span class="text-[10px] px-2 py-0.5 rounded-md font-semibold bg-slate-800 text-slate-300">
                                    {{ auth()->id() === $activeConversation->seller_id ? 'Comprador' : 'Vendedor' }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-400 flex items-center gap-2">
                                <span><span aria-hidden="true">🔒</span> Chat Privado</span>
                                <span>•</span>
                                <span>Dados pessoais protegidos</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card de Contexto do Anúncio -->
                    <div class="bg-slate-950/70 border border-slate-800 px-3 py-2 rounded-xl flex items-center justify-between gap-3 sm:max-w-xs">
                        <div class="truncate">
                            <span class="text-[10px] text-slate-500 block truncate">{{ $activeConversation->listing->game->name }}</span>
                            <a href="{{ route('listings.show', $activeConversation->listing->slug) }}" class="text-xs font-bold text-brand-300 hover:underline truncate block" target="_blank" title="Ver Anúncio">
                                {{ $activeConversation->listing->title }}
                            </a>
                        </div>
                        <span class="text-xs font-extrabold text-white shrink-0">
                            R$ {{ number_format($activeConversation->listing->price, 2, ',', '.') }}
                        </span>
                    </div>
                </div>

                <!-- Histórico de Mensagens com Rolagem -->
                <div class="flex-1 p-4 sm:p-6 overflow-y-auto space-y-4 max-h-[460px] min-h-[380px]" x-ref="messagesContainer">
                    <template x-if="hasOlder">
                        <div class="flex justify-center">
                            <button type="button" @click="loadOlderMessages()" :disabled="isLoadingOlder || accessRevoked"
                                    class="min-h-[44px] px-4 rounded-xl border border-slate-700 bg-slate-900 text-xs font-semibold text-slate-300 hover:text-white hover:border-slate-600 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-brand-500">
                                <span x-text="isLoadingOlder ? 'Carregando...' : 'Carregar mensagens anteriores'"></span>
                            </button>
                        </div>
                    </template>

                    <template x-if="messages.length === 0">
                        <div class="h-full flex flex-col items-center justify-center text-center p-8 text-slate-500">
                            <span aria-hidden="true" class="text-4xl mb-2">💬</span>
                            <p class="text-sm font-semibold text-slate-400">Inicie a conversa!</p>
                            <p class="text-xs mt-1 max-w-sm">Tire dúvidas sobre o produto, entrega ou combine detalhes com respeito e segurança.</p>
                        </div>
                    </template>

                    <template x-for="msg in messages" :key="msg.sender_id + ':' + (msg.client_uuid || msg.id)">
                        <div class="flex flex-col" :class="msg.sender_id === authId ? 'items-end' : 'items-start'">
                            <div class="max-w-[85%] sm:max-w-[70%] rounded-2xl p-3 text-xs leading-relaxed shadow-sm transition"
                                 :class="msg.sender_id === authId
                                    ? 'bg-brand-600 text-slate-950 font-medium rounded-tr-none'
                                    : 'bg-slate-800 text-slate-100 rounded-tl-none border border-slate-700/60'">
                                <div class="text-[10px] font-bold opacity-75 mb-0.5" x-text="msg.sender_name"></div>
                                <div class="whitespace-pre-wrap break-words" x-text="msg.body"></div>
                                <div class="mt-1 flex items-center justify-end gap-1.5 text-[9px] opacity-75">
                                    <span x-text="msg.formatted_time"></span>
                                    <template x-if="msg.status === 'sending'">
                                        <span><span aria-hidden="true">⏳</span><span class="sr-only">Enviando</span></span>
                                    </template>
                                    <template x-if="msg.status === 'sent' || (!msg.status && msg.id)">
                                        <span><span aria-hidden="true">✓</span><span class="sr-only">Enviada</span></span>
                                    </template>
                                    <template x-if="msg.status === 'error'">
                                        <span class="text-rose-950 font-bold"><span aria-hidden="true">⚠️</span> Falha</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Botão de Retry para mensagens com erro -->
                            <template x-if="msg.status === 'error'">
                                <button type="button" @click="retryMessage(msg)" class="mt-1 text-[10px] text-rose-400 hover:text-rose-300 underline font-semibold flex items-center gap-1 focus:outline-none focus:ring-1 focus:ring-rose-500">
                                    <span aria-hidden="true">🔄</span> Tentar novamente
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Formulário de Envio de Mensagem (Limitação v1: Apenas Texto, 1-2000 chars) -->
                <div class="p-4 border-t border-slate-800 bg-slate-950/60">
                    <template x-if="accessRevoked">
                        <p role="alert" class="mb-3 rounded-xl border border-rose-500/40 bg-rose-950/40 px-3 py-2 text-xs text-rose-200">
                            Seu acesso a esta conversa foi encerrado. Entre novamente para continuar; contas suspensas não podem ler nem enviar mensagens.
                        </p>
                    </template>
                    <form @submit.prevent="sendMessage()" class="space-y-2">
                        @csrf
                        <div class="relative">
                            <label for="chat-message-input" class="sr-only">Digite sua mensagem</label>
                            <textarea id="chat-message-input"
                                      rows="2"
                                      x-model="draftText"
                                      @input="saveDraft()"
                                      @keydown.enter="if (!$event.shiftKey && !$event.isComposing) { $event.preventDefault(); sendMessage(); }"
                                      maxlength="2000"
                                      placeholder="Digite sua mensagem... (Enter para enviar, Shift+Enter para nova linha)"
                                      class="w-full bg-slate-900 border border-slate-700 rounded-2xl p-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 resize-none transition"
                                      :disabled="isSubmitting || accessRevoked"></textarea>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-500 font-mono" :class="{ 'text-amber-400': draftText.length > 1800 }">
                                    <span x-text="draftText.length"></span>/2000 caracteres
                                </span>
                                <p x-show="sendError" x-text="sendError" role="status" class="text-[11px] text-rose-300 mt-1"></p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="submit"
                                        :disabled="isSubmitting || accessRevoked || draftText.trim().length === 0"
                                        class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 disabled:opacity-40 disabled:hover:bg-brand-600 text-slate-950 font-bold text-xs transition flex items-center gap-1.5 shadow-lg shadow-brand-600/10 focus:outline-none focus:ring-2 focus:ring-brand-400">
                                    <span x-show="!isSubmitting">Enviar</span>
                                    <span x-show="isSubmitting">Enviando...</span>
                                    <span>&rarr;</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @else
                <!-- Estado vazio quando nenhuma conversa foi selecionada -->
                <div class="h-full flex flex-col items-center justify-center text-center p-12 text-slate-500">
                    <span aria-hidden="true" class="text-5xl mb-3">💬</span>
                    <h3 class="text-base font-bold text-white mb-1">Selecione uma conversa</h3>
                    <p class="text-xs max-w-sm">Escolha uma conversa na lista à esquerda ou inicie uma nova na página de qualquer anúncio disponível.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@if($realtime)
@push('scripts')
{{-- Echo + Pusher (protocolo do Reverb), versões fixas com SRI. Só o sinal chega pelo WebSocket. --}}
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js" integrity="sha384-F0kpynobTwTZnUdhUu3W6P6QcdLW2hKKDTh6ECx+p/osH1JYIKlYqUjNc4ObdiTj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.1.6/dist/echo.iife.js" integrity="sha384-6L2VYkekunGYbXLTqWrFg9BaCXq+K+4jS8gNp6D5KTjCvnDE1mTc3MI10W52pLUS" crossorigin="anonymous"></script>
<script data-chat-realtime>
(function (realtime) {
    if (!realtime || !window.Pusher || !window.Echo || typeof window.Echo.default !== 'function') {
        return;
    }
    const EchoClient = window.Echo.default;
    window.Echo = new EchoClient({
        broadcaster: 'reverb',
        key: realtime.key,
        wsHost: realtime.host,
        wsPort: realtime.port,
        wssPort: realtime.port,
        forceTLS: realtime.scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: {{ Js::from(url('/broadcasting/auth')) }},
        auth: { headers: { 'X-CSRF-TOKEN': {{ Js::from(csrf_token()) }}, 'Accept': 'application/json' } },
    });
})({{ Js::from($realtime) }});
</script>
@endpush
@endif

@push('scripts')
<script>
function chatInbox(config) {
    const DENIED_STATUSES = [401, 403, 419];
    const MAX_SYNC_PAGES = 10;
    const REALTIME_POLL_MS = 15000;

    return {
        authId: config.authId,
        authName: config.authName,
        activeConversationId: config.activeConversationId,
        messages: config.initialMessages || [],
        hasOlder: !!config.hasOlder,
        isLoadingOlder: false,
        draftText: '',
        sendError: '',
        isSubmitting: false,
        mobileShowChat: true,
        connectionStatus: 'connected',
        connectionStatusText: 'Sincronizado',
        screenReaderAnnouncement: '',
        csrfToken: config.csrfToken,
        messagesUrl: config.messagesUrl,
        sendUrl: config.sendUrl,
        readUrl: config.readUrl,
        pollInterval: null,
        lastFetchedId: Math.max(0, ...(config.initialMessages || []).map(message => message.id)),
        lastSyncAt: 0,
        isFetching: false,
        syncPending: false,
        realtimeConnected: false,
        accessRevoked: false,

        init() {
            if (!this.activeConversationId) return;

            // Restaurar rascunho salvo localmente
            let savedDraft = null;
            try {
                savedDraft = localStorage.getItem('tm_chat_draft_' + this.activeConversationId);
            } catch (err) {
                // O navegador pode bloquear o armazenamento local.
            }
            if (savedDraft) {
                this.draftText = savedDraft;
            }

            this.scrollToBottom();
            this.markCurrentAsRead();
            this.setupEcho();

            // Sem WebSocket, sincroniza a cada 4s. Com ele, só confere a cada 15s
            // (perda de sinal e revogação de acesso).
            this.pollInterval = setInterval(() => {
                if (this.realtimeConnected && Date.now() - this.lastSyncAt < REALTIME_POLL_MS) return;
                this.fetchIncrementalMessages();
            }, 4000);
        },

        saveDraft() {
            if (this.activeConversationId) {
                try {
                    localStorage.setItem('tm_chat_draft_' + this.activeConversationId, this.draftText);
                } catch (err) {
                    // O envio continua disponível sem persistência de rascunhos.
                }
            }
        },

        destroy() {
            clearInterval(this.pollInterval);
            this.leaveChannel();
        },

        setStatus(status, text) {
            this.connectionStatus = status;
            this.connectionStatusText = text;
        },

        leaveChannel() {
            if (window.Echo && typeof window.Echo.leave === 'function' && this.activeConversationId) {
                window.Echo.leave('chat.' + this.activeConversationId);
            }
        },

        setupEcho() {
            const echo = window.Echo;
            if (!echo || typeof echo.private !== 'function' || !this.activeConversationId) return;

            try {
                // O evento traz só ids; o texto vem do HTTP, que revalida o acesso a cada leitura.
                echo.private('chat.' + this.activeConversationId)
                    .listen('.message.sent', () => this.fetchIncrementalMessages())
                    .error(() => {
                        this.realtimeConnected = false;
                        this.fetchIncrementalMessages();
                    });

                const connection = echo.connector && echo.connector.pusher && echo.connector.pusher.connection;
                if (connection) {
                    connection.bind('state_change', (states) => {
                        if (this.accessRevoked) return;
                        if (states.current === 'connected') {
                            this.realtimeConnected = true;
                            this.setStatus('connected', 'Tempo real ativo');
                            // Reconexão: recupera o que chegou enquanto a conexão estava fora.
                            this.fetchIncrementalMessages();
                        } else {
                            this.realtimeConnected = false;
                            if (states.current === 'connecting' || states.current === 'unavailable') {
                                this.setStatus('reconnecting', 'Reconectando...');
                            }
                        }
                    });
                }
            } catch (err) {
                console.warn('Echo indisponível; usando sincronização HTTP:', err);
            }
        },

        revokeAccess() {
            this.accessRevoked = true;
            this.realtimeConnected = false;
            clearInterval(this.pollInterval);
            this.leaveChannel();
            this.setStatus('revoked', 'Acesso encerrado');
            this.screenReaderAnnouncement = 'Seu acesso a esta conversa foi encerrado.';
        },

        newClientUuid() {
            const cryptoApi = typeof crypto !== 'undefined' ? crypto : null;
            if (cryptoApi && typeof cryptoApi.randomUUID === 'function') {
                return cryptoApi.randomUUID();
            }

            const bytes = new Uint8Array(16);
            if (cryptoApi && typeof cryptoApi.getRandomValues === 'function') {
                cryptoApi.getRandomValues(bytes);
            } else {
                for (let i = 0; i < bytes.length; i++) bytes[i] = Math.floor(Math.random() * 256);
            }
            bytes[6] = (bytes[6] & 0x0f) | 0x40;
            bytes[8] = (bytes[8] & 0x3f) | 0x80;
            const hex = Array.from(bytes, b => b.toString(16).padStart(2, '0')).join('');

            return hex.slice(0, 8) + '-' + hex.slice(8, 12) + '-' + hex.slice(12, 16) + '-' + hex.slice(16, 20) + '-' + hex.slice(20);
        },

        handleIncomingMessage(incoming) {
            // Deduplicação por client_uuid ou por id
            const existingIndex = this.messages.findIndex(m =>
                (incoming.client_uuid && m.client_uuid === incoming.client_uuid && m.sender_id === incoming.sender_id) ||
                (incoming.id && m.id === incoming.id)
            );

            if (existingIndex !== -1) {
                this.messages[existingIndex] = Object.assign({}, this.messages[existingIndex], incoming, { status: 'sent' });
            } else {
                this.messages.push(Object.assign({}, incoming, { status: 'sent' }));
                this.screenReaderAnnouncement = 'Nova mensagem de ' + incoming.sender_name + ': ' + incoming.body;
            }

            this.messages.sort((a, b) => (a.id || Infinity) - (b.id || Infinity));
            this.scrollToBottom();
        },

        async sendMessage() {
            const text = this.draftText.trim();
            if (!text || this.isSubmitting || this.accessRevoked) return;

            // Um UUID por envio; a nova tentativa reaproveita o mesmo e o servidor não duplica.
            const clientUuid = this.newClientUuid();
            const now = new Date();
            const formattedTime = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

            const pendingMessage = {
                client_uuid: clientUuid,
                sender_id: this.authId,
                sender_name: this.authName,
                body: text,
                formatted_time: formattedTime,
                status: 'sending'
            };

            this.messages.push(pendingMessage);
            this.draftText = '';
            this.saveDraft();
            this.scrollToBottom();

            await this.dispatchMessageRequest(this.messages.find(message =>
                message.sender_id === this.authId && message.client_uuid === clientUuid
            ));
        },

        async retryMessage(msg) {
            if (this.isSubmitting || this.accessRevoked) return;
            msg.status = 'sending';
            await this.dispatchMessageRequest(msg);
        },

        async dispatchMessageRequest(msg) {
            this.isSubmitting = true;
            this.sendError = '';
            try {
                const response = await fetch(this.sendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        body: msg.body,
                        client_uuid: msg.client_uuid
                    })
                });

                if (DENIED_STATUSES.includes(response.status)) {
                    msg.status = 'error';
                    this.revokeAccess();
                    return;
                }

                if (response.status === 429) {
                    msg.status = 'error';
                    this.sendError = 'Muitas mensagens em pouco tempo. Aguarde um minuto e tente novamente.';
                    return;
                }

                if (response.status === 422) {
                    const data = await response.json().catch(() => ({}));
                    const errors = data && data.errors ? Object.values(data.errors).flat() : [];
                    msg.status = 'error';
                    this.sendError = errors[0] || 'A mensagem não pôde ser enviada.';
                    return;
                }

                if (!response.ok) {
                    throw new Error('Falha HTTP: ' + response.status);
                }

                const data = await response.json();
                if (data && data.message) {
                    this.handleIncomingMessage(data.message);
                }
            } catch (error) {
                msg.status = 'error';
                console.error('Erro no envio da mensagem:', error);
            } finally {
                this.isSubmitting = false;
                this.scrollToBottom();
            }
        },

        async fetchIncrementalMessages() {
            if (!this.messagesUrl || this.accessRevoked) return;
            if (this.isFetching) {
                // Um sinal chegou durante a busca: sincroniza de novo ao terminar.
                this.syncPending = true;
                return;
            }
            this.isFetching = true;

            try {
                let hasMore = true;
                for (let page = 0; hasMore && page < MAX_SYNC_PAGES; page++) {
                    const response = await fetch(this.messagesUrl + '?after_id=' + this.lastFetchedId, {
                        headers: { 'Accept': 'application/json' }
                    });

                    if (DENIED_STATUSES.includes(response.status)) {
                        this.revokeAccess();
                        return;
                    }
                    if (!response.ok) throw new Error('Falha HTTP: ' + response.status);

                    const data = await response.json();
                    const batch = (data && data.messages) || [];
                    batch.forEach(m => this.handleIncomingMessage(m));
                    if (batch.length > 0) {
                        this.lastFetchedId = Math.max(this.lastFetchedId, ...batch.map(m => m.id));
                    }
                    hasMore = !!(data && data.has_more) && batch.length > 0;
                }

                this.lastSyncAt = Date.now();
                this.setStatus('connected', this.realtimeConnected ? 'Tempo real ativo' : 'Sincronizado');
                await this.markCurrentAsRead();
            } catch (err) {
                this.setStatus('reconnecting', 'Reconectando...');
            } finally {
                this.isFetching = false;
                if (this.syncPending && !this.accessRevoked) {
                    this.syncPending = false;
                    this.fetchIncrementalMessages();
                }
            }
        },

        async loadOlderMessages() {
            if (!this.hasOlder || this.isLoadingOlder || this.accessRevoked || !this.messagesUrl) return;
            const ids = this.messages.filter(m => m.id).map(m => m.id);
            if (ids.length === 0) return;

            this.isLoadingOlder = true;
            const container = this.$refs.messagesContainer;
            const previousHeight = container ? container.scrollHeight : 0;

            try {
                const response = await fetch(this.messagesUrl + '?before_id=' + Math.min(...ids), {
                    headers: { 'Accept': 'application/json' }
                });

                if (DENIED_STATUSES.includes(response.status)) {
                    this.revokeAccess();
                    return;
                }
                if (!response.ok) throw new Error('Falha HTTP: ' + response.status);

                const data = await response.json();
                const known = new Set(ids);
                const older = ((data && data.messages) || [])
                    .filter(m => !known.has(m.id))
                    .map(m => Object.assign({}, m, { status: 'sent' }));

                this.messages = older.concat(this.messages);
                this.hasOlder = !!(data && data.has_more);
                this.$nextTick(() => {
                    if (container) container.scrollTop = container.scrollHeight - previousHeight;
                });
            } catch (err) {
                this.screenReaderAnnouncement = 'Não foi possível carregar as mensagens anteriores.';
            } finally {
                this.isLoadingOlder = false;
            }
        },

        async markCurrentAsRead() {
            if (!this.readUrl || !this.lastFetchedId || this.accessRevoked) return;

            try {
                await fetch(this.readUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        last_read_message_id: this.lastFetchedId
                    })
                });
            } catch (err) {
                // Silencioso: o cursor avança na próxima sincronização.
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        }
    };
}
</script>
@endpush
@endsection
