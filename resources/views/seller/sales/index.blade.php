@extends('layouts.app')

@section('title', 'Painel do Vendedor - Vendas')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                <span>📈</span> Gestão de Vendas
            </h1>
            <p class="text-xs text-slate-400">Acompanhe os pedidos recebidos e confirme a entrega digital.</p>
        </div>
        <a href="{{ route('seller.anuncios.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 hover:text-white transition">
            &larr; Meus Anúncios
        </a>
    </div>

    <div class="space-y-4">
        @forelse($sales as $sale)
            <div class="rounded-2xl bg-slate-900 border border-slate-800 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800 text-xs">
                    <div>
                        <span class="text-slate-400">Pedido:</span>
                        <span class="font-mono font-bold text-white ml-1">{{ $sale->order->order_number }}</span>
                        <span class="text-slate-500 ml-2">| Comprador: {{ $sale->order->buyer->name }}</span>
                    </div>
                    <div>
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider
                            {{ $sale->delivery_status === 'entregue' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' :
                               'bg-amber-950 text-amber-400 border border-amber-800' }}">
                            {{ $sale->delivery_status }}
                        </span>
                    </div>
                </div>

                <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-sm text-white">{{ $sale->listing->title }}</h3>
                        <span class="text-xs text-indigo-400 font-extrabold mt-1 block">
                            R$ {{ number_format($sale->unit_price, 2, ',', '.') }}
                        </span>
                    </div>

                    @if($sale->delivery_status !== 'entregue')
                        <form action="{{ route('seller.sales.deliver', $sale) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-emerald-600/20">
                                Confirmar Entrega do Item
                            </button>
                        </form>
                    @else
                        <span class="text-xs text-emerald-400 font-semibold">
                            ✓ Entregue em {{ $sale->delivered_at?->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-slate-900 border border-slate-800 p-12 text-center text-slate-500 text-sm">
                Nenhuma venda registrada até o momento.
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $sales->links() }}
    </div>
</div>
@endsection
