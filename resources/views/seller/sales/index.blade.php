@extends('layouts.app')

@section('title', 'Painel do Vendedor - Vendas')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5 mb-8">
        <div>
            <x-page-heading title="Gestão de Vendas" eyebrow="DA SUA LOJA PARA O PRÓXIMO PLAYER" description="Acompanhe pedidos recebidos, instruções e entregas digitais." />
        </div>
        <a href="{{ route('seller.anuncios.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 hover:text-white transition">
            &larr; Meus Anúncios
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-rose-800 bg-rose-950/60 p-4 text-sm text-rose-200" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse($sales as $sale)
            @php($isCoaching = $sale->listing?->category?->type === 'service')
            @php($completedSessions = $sale->delivery_status === 'entregue' ? $sale->quantity : $sale->deliveries->count())
            <div class="rounded-2xl bg-slate-900 border border-slate-800 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800 text-xs">
                    <div>
                        <span class="text-slate-400">Pedido:</span>
                        <span class="font-mono font-bold text-white ml-1">{{ $sale->order->order_number }}</span>
                        <span class="text-slate-500 ml-2">| Comprador: {{ $sale->order->buyer->name }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-lg border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $sale->order->isPaid() ? 'border-emerald-800 bg-emerald-950 text-emerald-400' : 'border-amber-800 bg-amber-950 text-amber-400' }}">
                            Pedido {{ str_replace('_', ' ', $sale->order->status) }}
                        </span>
                        <span class="rounded-lg border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $sale->delivery_status === 'entregue' ? 'border-emerald-800 bg-emerald-950 text-emerald-400' : 'border-slate-700 bg-slate-800 text-slate-300' }}">
                            {{ str_replace('_', ' ', $sale->delivery_status) }}
                        </span>
                    </div>
                </div>

                <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-sm text-white">{{ $sale->listing?->title ?? 'Anúncio indisponível' }}</h3>
                        <span class="text-xs text-brand-400 font-extrabold mt-1 block">
                            R$ {{ number_format($sale->unit_price, 2, ',', '.') }} · {{ $sale->quantity }} {{ $isCoaching ? 'sessão(ões)' : 'unidade(s)' }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        @if($sale->listing)
                            <form action="{{ route('chat.start') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="listing_id" value="{{ $sale->listing_id }}">
                                <input type="hidden" name="buyer_id" value="{{ $sale->order->buyer_id }}">
                                <button type="submit" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition flex items-center gap-1.5 border border-slate-700">
                                    <span>💬</span> Falar com comprador
                                </button>
                            </form>
                        @endif

                        @if($sale->delivery_status !== 'entregue' && $sale->order->isPaid())
                            <form action="{{ route('seller.sales.deliver', $sale) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PATCH')
                                @if($isCoaching)
                                    <input type="hidden" name="session_number" value="{{ $completedSessions + 1 }}">
                                @endif
                                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-emerald-600/20">
                                    @if($isCoaching)
                                        Registrar sessão {{ $completedSessions + 1 }} de {{ $sale->quantity }}
                                    @else
                                        Confirmar entrega do item
                                    @endif
                                </button>
                            </form>
                        @elseif($sale->delivery_status !== 'entregue')
                            <span class="text-xs text-amber-300">A entrega será liberada após a confirmação do pagamento.</span>
                        @else
                            <span class="text-xs text-emerald-400 font-semibold">
                                ✓ Entregue em {{ $sale->delivered_at?->format('d/m/Y H:i') }}
                            </span>
                        @endif
                    </div>
                </div>

                @if(filled($sale->order->notes))
                    <div class="mb-4 rounded-xl border border-slate-800 bg-slate-950 p-4">
                        <h4 class="mb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">Instruções do comprador / Trade URL</h4>
                        <p class="whitespace-pre-line break-words text-xs text-slate-200">{{ $sale->order->notes }}</p>
                    </div>
                @endif

                @if($isCoaching)
                    <div class="rounded-xl border border-indigo-900/60 bg-indigo-950/30 p-4">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-indigo-200">Sessões de coaching concluídas</span>
                            <span class="font-mono font-bold text-white">{{ $completedSessions }} / {{ $sale->quantity }}</span>
                        </div>
                        @if($sale->deliveries->isNotEmpty())
                            <ul class="mt-2 flex flex-wrap gap-2">
                                @foreach($sale->deliveries as $delivery)
                                    <li class="rounded-lg bg-slate-950 px-2 py-1 text-[10px] text-slate-400">
                                        Sessão {{ $delivery->delivery_number }} · {{ $delivery->delivered_at->format('d/m/Y H:i') }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <x-empty-state title="Nenhuma venda registrada até o momento." description="Seus pedidos recebidos aparecerão aqui. Prepare seus anúncios para o próximo player." icon="store" :href="route('seller.anuncios.index')" action="Ver meus anúncios" />
        @endforelse
    </div>

    <div class="mt-8">
        {{ $sales->links() }}
    </div>
</div>
@endsection
