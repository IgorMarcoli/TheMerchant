@extends('layouts.app')

@section('title', "Pedido #{$order->order_number}")

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Topo / Navegação -->
    <div class="flex items-center justify-between">
        <a href="{{ route('orders.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1 focus:outline-none focus:ring-1 focus:ring-brand-500 rounded p-1">
            &larr; Voltar aos Meus Pedidos
        </a>
        <div class="flex items-center gap-2">
            <!-- Badge de Status do Pedido -->
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                {{ $order->status === 'concluido' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' :
                   ($order->status === 'pago' ? 'bg-brand-950 text-brand-400 border border-brand-800' :
                   ($order->status === 'em_andamento' ? 'bg-cyan-950 text-cyan-400 border border-cyan-800' :
                   ($order->status === 'cancelado' ? 'bg-rose-950 text-rose-400 border border-rose-800' :
                   'bg-amber-950 text-amber-400 border border-amber-800'))) }}">
                ● Status do Pedido: {{ str_replace('_', ' ', ucfirst($order->status)) }}
            </span>
        </div>
    </div>

    <!-- Card de Resumo Principal -->
    <div class="rounded-3xl bg-slate-900 border border-slate-800 p-6 sm:p-8 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-6 border-b border-slate-800">
            <div>
                <span class="text-xs text-brand-400 font-bold uppercase tracking-wider block mb-1">Comprovante de Compra</span>
                <h1 class="text-xl sm:text-2xl font-black text-white flex items-center gap-2">
                    <span>Pedido</span>
                    <span class="font-mono text-brand-300">#{{ $order->order_number }}</span>
                </h1>
                <span class="text-xs text-slate-400 mt-1 block">
                    Realizado em {{ $order->created_at->format('d/m/Y \à\s H:i:s') }}
                </span>
            </div>
            <div class="sm:text-right">
                <span class="text-xs text-slate-400 block mb-0.5">Total Consolidado</span>
                <span class="text-2xl sm:text-3xl font-black text-brand-400">
                    R$ {{ number_format($order->total_amount, 2, ',', '.') }}
                </span>
                <span class="text-[11px] text-slate-500 block mt-0.5">Preços congelados no ato da compra</span>
            </div>
        </div>

        <!-- 🧭 Timeline do Ciclo de Vida do Pedido (RF13, Contrato #13/#14) -->
        <div class="py-6 border-b border-slate-800">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                <span>⏱️</span> Linha do Tempo do Pedido
            </h2>

            @if($order->status === 'cancelado')
                <div class="p-4 rounded-2xl bg-rose-950/40 border border-rose-800/60 text-xs text-rose-300 flex items-center gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <strong class="font-bold block text-sm">Pedido Cancelado</strong>
                        <span>Este pedido foi cancelado ou expirou antes da confirmação de pagamento pelo gateway. Nenhum valor pendente será cobrado.</span>
                    </div>
                </div>
            @else
                @php
                    $isPending = in_array($order->status, ['pendente', 'pago', 'em_andamento', 'concluido']);
                    $isPaid = in_array($order->status, ['pago', 'em_andamento', 'concluido']);
                    $isInProgress = in_array($order->status, ['em_andamento', 'concluido']);
                    $isCompleted = ($order->status === 'concluido');
                @endphp

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 relative">
                    <!-- Etapa 1: Pedido Realizado -->
                    <div class="flex flex-col items-center text-center p-3 rounded-2xl {{ $isPending ? 'bg-slate-950/60 border border-slate-800' : 'opacity-40' }}">
                        <div class="w-8 h-8 rounded-full mb-2 flex items-center justify-center text-xs font-bold {{ $isPaid ? 'bg-emerald-500 text-slate-950' : 'bg-brand-500 text-slate-950 animate-pulse' }}">
                            {{ $isPaid ? '✓' : '1' }}
                        </div>
                        <span class="text-xs font-bold text-white">1. Pedido Criado</span>
                        <span class="text-[10px] text-slate-400 mt-0.5">Pendente</span>
                        <span class="text-[9px] text-slate-500 mt-1 font-mono">{{ $order->created_at->format('d/m H:i') }}</span>
                    </div>

                    <!-- Etapa 2: Pagamento Aprovado -->
                    <div class="flex flex-col items-center text-center p-3 rounded-2xl {{ $isPaid ? 'bg-slate-950/60 border border-slate-800' : 'bg-slate-950/20 border border-slate-900 opacity-50' }}">
                        <div class="w-8 h-8 rounded-full mb-2 flex items-center justify-center text-xs font-bold {{ $isPaid ? ($isInProgress ? 'bg-emerald-500 text-slate-950' : 'bg-brand-500 text-slate-950') : 'bg-slate-800 text-slate-400' }}">
                            {{ $isPaid ? '✓' : '2' }}
                        </div>
                        <span class="text-xs font-bold text-white">2. Pagamento</span>
                        <span class="text-[10px] text-slate-400 mt-0.5">
                            {{ $isPaid ? 'Pago via Gateway' : 'Aguardando' }}
                        </span>
                        <span class="text-[9px] text-slate-500 mt-1 font-mono">
                            {{ $order->payment ? $order->payment->created_at->format('d/m H:i') : ($isPaid ? $order->updated_at->format('d/m H:i') : '--') }}
                        </span>
                    </div>

                    <!-- Etapa 3: Em Andamento / Em Entrega -->
                    <div class="flex flex-col items-center text-center p-3 rounded-2xl {{ $isInProgress || ($order->status === 'pago') ? 'bg-slate-950/60 border border-slate-800' : 'bg-slate-950/20 border border-slate-900 opacity-50' }}">
                        <div class="w-8 h-8 rounded-full mb-2 flex items-center justify-center text-xs font-bold {{ $isCompleted ? 'bg-emerald-500 text-slate-950' : ($isPaid ? 'bg-cyan-500 text-slate-950 animate-pulse' : 'bg-slate-800 text-slate-400') }}">
                            {{ $isCompleted ? '✓' : '3' }}
                        </div>
                        <span class="text-xs font-bold text-white">3. Em Andamento</span>
                        <span class="text-[10px] text-slate-400 mt-0.5">
                            {{ $isCompleted ? 'Itens Prontos' : ($isPaid ? 'Em Entrega Digital' : 'Pendente') }}
                        </span>
                        <span class="text-[9px] text-slate-500 mt-1">Vendedores acionados</span>
                    </div>

                    <!-- Etapa 4: Concluído -->
                    <div class="flex flex-col items-center text-center p-3 rounded-2xl {{ $isCompleted ? 'bg-slate-950/60 border border-emerald-800/50' : 'bg-slate-950/20 border border-slate-900 opacity-50' }}">
                        <div class="w-8 h-8 rounded-full mb-2 flex items-center justify-center text-xs font-bold {{ $isCompleted ? 'bg-emerald-400 text-slate-950' : 'bg-slate-800 text-slate-400' }}">
                            {{ $isCompleted ? '✓' : '4' }}
                        </div>
                        <span class="text-xs font-bold text-white">4. Concluído</span>
                        <span class="text-[10px] text-slate-400 mt-0.5">
                            {{ $isCompleted ? 'Entrega Finalizada' : 'Aguardando Entrega' }}
                        </span>
                        <span class="text-[9px] text-slate-500 mt-1">Avaliação liberada</span>
                    </div>
                </div>

                <div class="mt-3 p-2.5 rounded-xl bg-slate-950/40 border border-slate-800/80 text-[11px] text-slate-400 text-center">
                    💡 <em>A timeline acima reflete o estado do pedido. A entrega de cada cosmético ou serviço é gerenciada e rastreada de forma independente pelos vendedores nos itens abaixo.</em>
                </div>
            @endif
        </div>

        <!-- 💳 Detalhes de Pagamento do Gateway (RF11, RF12, RNF04) -->
        <div class="py-6 border-b border-slate-800">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                <span>🛡️</span> Detalhes do Pagamento
            </h2>

            @if($order->payment)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4 rounded-2xl bg-slate-950/70 border border-slate-800 text-xs">
                    <div>
                        <span class="text-slate-500 block text-[10px] uppercase font-semibold">Provedor / Gateway</span>
                        <span class="font-bold text-white uppercase">{{ strtoupper($order->payment->gateway) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[10px] uppercase font-semibold">ID da Transação</span>
                        <span class="font-mono text-slate-300 truncate block" title="{{ $order->payment->transaction_id }}">
                            {{ $order->payment->transaction_id }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[10px] uppercase font-semibold">Status do Gateway</span>
                        <span class="font-semibold text-emerald-400">
                            ● {{ ucfirst($order->payment->status) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[10px] uppercase font-semibold">Valor Processado</span>
                        <span class="font-bold text-white">
                            R$ {{ number_format($order->payment->amount, 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            @else
                <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 text-xs flex items-center justify-between gap-4">
                    <div class="text-slate-400">
                        <span class="font-semibold text-white block mb-0.5">Aguardando Confirmação do Provedor de Pagamento</span>
                        <span>O processamento é realizado pelo gateway credenciado. Assim que o webhook assíncrono confirmar o pagamento, o status mudará para <strong>Pago</strong>.</span>
                    </div>
                    <span class="text-2xl">⏳</span>
                </div>
            @endif
        </div>

        <!-- 📝 Instruções do Pedido / Observações do Comprador (Trade URL / Horários) -->
        @if($order->notes)
            <div class="py-6 border-b border-slate-800">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <span>📌</span> Instruções do Comprador para Entrega
                </h2>
                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 text-xs text-slate-200 leading-relaxed font-mono whitespace-pre-wrap">
                    {{ $order->notes }}
                </div>
            </div>
        @endif

        <!-- 📦 Itens do Pedido, Quantidades, Duração, Instruções e Avaliação Pós-Entrega -->
        <div class="pt-6">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center justify-between">
                <span class="flex items-center gap-1.5">
                    <span>💎</span> Itens Contratados ({{ $order->items->count() }})
                </span>
                <span class="text-[11px] text-slate-500 font-normal">Entrega individual por item</span>
            </h2>

            <div class="space-y-6">
                @foreach($order->items as $item)
                    <div class="rounded-2xl bg-slate-950/60 border border-slate-800 p-5 sm:p-6 transition hover:border-slate-700">
                        <!-- Topo do Item: Identificação, Tipo e Status de Entrega -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800/80">
                            <div class="flex items-start sm:items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-2xl shrink-0">
                                    {{ ($item->listing?->category?->type === 'service') ? '🎓' : '💎' }}
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-bold text-sm text-white">
                                            @if($item->listing)
                                                <a href="{{ route('listings.show', $item->listing->slug) }}" target="_blank" class="hover:text-brand-300 transition hover:underline">
                                                    {{ $item->listing->title }}
                                                </a>
                                            @else
                                                Item Indisponível
                                            @endif
                                        </h3>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                            {{ ($item->listing?->category?->type === 'service') ? 'bg-purple-950 text-purple-300 border border-purple-800/60' : 'bg-brand-950 text-brand-300 border border-brand-800/60' }}">
                                            {{ ($item->listing?->category?->type === 'service') ? 'Serviço' : 'Cosmético Digital' }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                                        <span>Jogo: <strong class="text-slate-300">{{ $item->listing?->game?->name ?? 'Geral' }}</strong></span>
                                        <span>•</span>
                                        <span>Categoria: <strong class="text-slate-300">{{ $item->listing?->category?->name ?? 'Geral' }}</strong></span>
                                        <span>•</span>
                                        <span>Vendedor: <strong class="text-white">{{ $item->seller->name }}</strong></span>
                                        <span class="text-amber-400 text-xs font-semibold">★ {{ $item->seller->sellerProfile?->reputation_score ?? '5.00' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Badge de Status de Entrega Individual do Item -->
                            <div class="flex flex-col sm:items-end">
                                <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider
                                    {{ $item->delivery_status === 'entregue' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' :
                                       ($item->delivery_status === 'em_entrega' ? 'bg-cyan-950 text-cyan-400 border border-cyan-800' :
                                       'bg-slate-800 text-slate-400 border border-slate-700') }}">
                                    Entrega: {{ str_replace('_', ' ', ucfirst($item->delivery_status)) }}
                                </span>
                                @if($item->delivered_at)
                                    <span class="text-[10px] text-slate-500 mt-1 font-mono">
                                        Entregue em {{ $item->delivered_at->format('d/m/Y H:i') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Valores, Quantidades e Duração/Instruções do Item -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-4 border-b border-slate-800/80 text-xs">
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/60">
                                <span class="text-slate-500 block text-[10px] uppercase font-semibold">Quantidade Contratada</span>
                                <span class="font-extrabold text-white text-sm">
                                    {{ $item->quantity }} {{ ($item->listing?->category?->type === 'service') ? 'sessão(ões)' : 'unidade(s)' }}
                                </span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/60">
                                <span class="text-slate-500 block text-[10px] uppercase font-semibold">Preço Unitário (Congelado)</span>
                                <span class="font-extrabold text-white text-sm">
                                    R$ {{ number_format($item->unit_price, 2, ',', '.') }}
                                </span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/60">
                                <span class="text-slate-500 block text-[10px] uppercase font-semibold">Subtotal do Item</span>
                                <span class="font-extrabold text-brand-400 text-sm">
                                    R$ {{ number_format($item->unit_price * $item->quantity, 2, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <!-- Instruções e Duração do Item -->
                        <div class="py-3 text-xs leading-relaxed text-slate-300">
                            @if($item->listing?->category?->type === 'service')
                                <div class="p-3 rounded-xl bg-purple-950/30 border border-purple-900/40 text-purple-200">
                                    <strong class="font-bold flex items-center gap-1 text-purple-300 mb-1">
                                        <span>🎓</span> Duração e Condições do Serviço / Coaching:
                                    </strong>
                                    <p class="text-[11px] text-slate-300">
                                        A duração de cada sessão segue as especificações do anúncio. Utilize o chat direto abaixo para alinhar horário, link de Discord ou sala personalizada com o mentor.
                                    </p>
                                </div>
                            @else
                                <div class="p-3 rounded-xl bg-slate-900/50 border border-slate-800 text-slate-300">
                                    <strong class="font-bold flex items-center gap-1 text-brand-300 mb-1">
                                        <span>💎</span> Instruções de Envio do Cosmético Digital:
                                    </strong>
                                    <p class="text-[11px] text-slate-400">
                                        O envio é feito via troca direta na plataforma do jogo ou pelo Trade URL informado no pedido. Fique atento às notificações no chat.
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- Ações do Item: Chat Direto (RF21) -->
                        <div class="pt-4 flex flex-wrap items-center justify-between gap-3">
                            <!-- Botão Falar com Vendedor (Chat Integrado por item - Issue #31) -->
                            <form action="{{ route('chat.start') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="listing_id" value="{{ $item->listing_id }}">
                                <button type="submit"
                                        class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs transition flex items-center gap-1.5 border border-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500"
                                        aria-label="Falar com {{ $item->seller->name }}">
                                    <span>💬</span> Falar com Vendedor
                                </button>
                            </form>
                        </div>

                        <!-- Componente de Avaliação Pós-Entrega (RF15 / Issue #19/20) -->
                        <x-order-review :order="$order" :item="$item" />
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
