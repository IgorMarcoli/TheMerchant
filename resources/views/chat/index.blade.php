@extends('layouts.app')

@section('title', 'Caixa de Entrada e Mensagens')

@section('content')
<div class="max-w-6xl mx-auto" x-data="chatInbox({
    authId: {{ auth()->id() }},
    authName: '{{ addslashes(auth()->user()->name) }}',
    activeConversationId: {{ $activeConversation ? $activeConversation->id : 'null' }},
    initialMessages: {{ Js::from($messages->map->toSafeArray()) }},
    csrfToken: '{{ csrf_token() }}',
    messagesUrl: '{{ $activeConversation ? route('chat.messages', $activeConversation) : '' }}',
    sendUrl: '{{ $activeConversation ? route('chat.messages.store', $activeConversation) : '' }}',
    readUrl: '{{ $activeConversation ? route('chat.read', $activeConversation) : '' }}'
})">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                <span>💬</span> Mensagens & Negociações
            </h1>
            <p class="text-xs text-slate-400">Canal direto de texto entre comprador e vendedor com proteção de dados e privacidade.</p>
        </div>

        <div class="flex items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-slate-800 bg-slate-900/90 text-slate-300">
                <span class="w-2 h-2 rounded-full" :class="connectionStatus === 'connected' ? 'bg-emerald-400' : (connectionStatus === 'reconnecting' ? 'bg-amber-400 animate-pulse' : 'bg-slate-500')"></span>
                <span x-text="connectionStatusText"></span>
            </span>
        </div>
    </div>

    <!-- Região acessível para leitores de tela -->
    <div aria-live="polite" class="sr-only" x-text="screenReaderAnnouncement"></div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl min-h-[620px] grid grid-cols-1 lg:grid-cols-12">
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
                            <span>🎮</span>
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
                        <span class="text-3xl block mb-2">📭</span>
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
                            {{ substr($activeOther?->name ?? 'U', 0, 1) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-bold text-white">{{ $activeOther?->name ?? 'Usuário' }}</h2>
                                <span class="text-[10px] px-2 py-0.5 rounded-md font-semibold bg-slate-800 text-slate-300">
                                    {{ auth()->id() === $activeConversation->seller_id ? 'Comprador' : 'Vendedor' }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-400 flex items-center gap-2">
                                <span>🔒 Chat Privado</span>
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
                    <template x-if="messages.length === 0">
                        <div class="h-full flex flex-col items-center justify-center text-center p-8 text-slate-500">
                            <span class="text-4xl mb-2">💬</span>
                            <p class="text-sm font-semibold text-slate-400">Inicie a conversa!</p>
                            <p class="text-xs mt-1 max-w-sm">Tire dúvidas sobre o produto, entrega ou combine detalhes com respeito e segurança.</p>
                        </div>
                    </template>

                    <template x-for="msg in messages" :key="msg.client_uuid || msg.id">
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
                                        <span>⏳</span>
                                    </template>
                                    <template x-if="msg.status === 'sent' || (!msg.status && msg.id)">
                                        <span>✓</span>
                                    </template>
                                    <template x-if="msg.status === 'error'">
                                        <span class="text-rose-950 font-bold">⚠️ Falha</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Botão de Retry para mensagens com erro -->
                            <template x-if="msg.status === 'error'">
                                <button type="button" @click="retryMessage(msg)" class="mt-1 text-[10px] text-rose-400 hover:text-rose-300 underline font-semibold flex items-center gap-1 focus:outline-none focus:ring-1 focus:ring-rose-500">
                                    <span>🔄</span> Tentar novamente
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Formulário de Envio de Mensagem (Limitação v1: Apenas Texto, 1-2000 chars) -->
                <div class="p-4 border-t border-slate-800 bg-slate-950/60">
                    <form @submit.prevent="sendMessage()" class="space-y-2">
                        <div class="relative">
                            <label for="chat-message-input" class="sr-only">Digite sua mensagem</label>
                            <textarea id="chat-message-input"
                                      rows="2"
                                      x-model="draftText"
                                      @input="saveDraft()"
                                      @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                                      maxlength="2000"
                                      placeholder="Digite sua mensagem... (Enter para enviar, Shift+Enter para nova linha)"
                                      class="w-full bg-slate-900 border border-slate-700 rounded-2xl p-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 resize-none transition"
                                      :disabled="isSubmitting"></textarea>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-slate-500 font-mono" :class="{ 'text-amber-400': draftText.length > 1800 }">
                                <span x-text="draftText.length"></span>/2000 caracteres
                            </span>

                            <div class="flex items-center gap-2">
                                <button type="submit"
                                        :disabled="isSubmitting || draftText.trim().length === 0"
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
                    <span class="text-5xl mb-3">💬</span>
                    <h3 class="text-base font-bold text-white mb-1">Selecione uma conversa</h3>
                    <p class="text-xs max-w-sm">Escolha uma conversa na lista à esquerda ou inicie uma nova na página de qualquer anúncio disponível.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function chatInbox(config) {
    return {
        authId: config.authId,
        authName: config.authName,
        activeConversationId: config.activeConversationId,
        messages: config.initialMessages || [],
        draftText: '',
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

        init() {
            if (this.activeConversationId) {
                // Restaurar rascunho salvo localmente
                const savedDraft = localStorage.getItem('tm_chat_draft_' + this.activeConversationId);
                if (savedDraft) {
                    this.draftText = savedDraft;
                }

                this.scrollToBottom();
                this.markCurrentAsRead();

                // Conectar ao Echo se estiver configurado
                this.setupEcho();

                // Polling incremental de fallback
                this.pollInterval = setInterval(() => {
                    this.fetchIncrementalMessages();
                }, 4000);
            }
        },

        saveDraft() {
            if (this.activeConversationId) {
                localStorage.setItem('tm_chat_draft_' + this.activeConversationId, this.draftText);
            }
        },

        setupEcho() {
            if (window.Echo && this.activeConversationId) {
                try {
                    window.Echo.private('chat.' + this.activeConversationId)
                        .listen('.message.sent', (e) => {
                            if (e && e.message) {
                                this.handleIncomingMessage(e.message);
                            }
                        });

                    if (window.Echo.connector && window.Echo.connector.pusher) {
                        window.Echo.connector.pusher.connection.bind('state_change', (states) => {
                            if (states.current === 'connected') {
                                this.connectionStatus = 'connected';
                                this.connectionStatusText = 'Tempo Real Ativo';
                                this.fetchIncrementalMessages();
                            } else if (states.current === 'connecting' || states.current === 'unavailable') {
                                this.connectionStatus = 'reconnecting';
                                this.connectionStatusText = 'Reconectando...';
                            }
                        });
                    }
                } catch (err) {
                    console.warn('Echo listener fallback to HTTP sync:', err);
                }
            }
        },

        handleIncomingMessage(incoming) {
            // Deduplicação por client_uuid ou por id
            const existingIndex = this.messages.findIndex(m =>
                (incoming.client_uuid && m.client_uuid === incoming.client_uuid) ||
                (incoming.id && m.id === incoming.id)
            );

            if (existingIndex !== -1) {
                this.messages[existingIndex] = Object.assign({}, this.messages[existingIndex], incoming, { status: 'sent' });
            } else {
                this.messages.push(Object.assign({}, incoming, { status: 'sent' }));
                this.screenReaderAnnouncement = 'Nova mensagem de ' + incoming.sender_name + ': ' + incoming.body;
            }

            this.scrollToBottom();
            this.markCurrentAsRead();
        },

        async sendMessage() {
            const text = this.draftText.trim();
            if (!text || this.isSubmitting) return;

            const clientUuid = 'msg_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);
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

            await this.dispatchMessageRequest(pendingMessage);
        },

        async retryMessage(msg) {
            msg.status = 'sending';
            await this.dispatchMessageRequest(msg);
        },

        async dispatchMessageRequest(msg) {
            this.isSubmitting = true;
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

                if (!response.ok) {
                    throw new Error('Falha HTTP: ' + response.status);
                }

                const data = await response.json();
                if (data && data.message) {
                    msg.id = data.message.id;
                    msg.status = 'sent';
                    msg.formatted_time = data.message.formatted_time;
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
            if (!this.messagesUrl) return;

            const lastMessage = this.messages.filter(m => m.id).slice(-1)[0];
            const afterId = lastMessage ? lastMessage.id : 0;

            try {
                const response = await fetch(this.messagesUrl + '?after_id=' + afterId, {
                    headers: { 'Accept': 'application/json' }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data && data.messages && data.messages.length > 0) {
                        data.messages.forEach(m => this.handleIncomingMessage(m));
                    }
                }
            } catch (err) {
                // Silencioso em caso de falha transitória
            }
        },

        async markCurrentAsRead() {
            if (!this.readUrl) return;
            const lastSentOrReceived = this.messages.filter(m => m.id).slice(-1)[0];
            if (!lastSentOrReceived) return;

            try {
                await fetch(this.readUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        last_read_message_id: lastSentOrReceived.id
                    })
                });
            } catch (err) {
                // Silencioso
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

