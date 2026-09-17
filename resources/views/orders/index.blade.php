@extends('layouts.app')

@section('title', 'Meus Pedidos')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white flex items-center gap-2">
            <span>📦</span> Histórico de Pedidos
        </h1>
        <p class="text-xs text-slate-400">Acompanhe suas compras e realize avaliações pós-entrega.</p>
    </div>

    <div class="space-y-4">
        @forelse($orders as $order)
            <div class="rounded-2xl bg-slate-900 border border-slate-800 p-6 hover:border-slate-700 transition">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800 text-xs">
                    <div>
                        <span class="text-slate-400">Pedido:</span>
                        <span class="font-mono font-bold text-white ml-1">{{ $order->order_number }}</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-slate-400">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                        <span class="px-2.5 py-1 rounded-lg font-bold text-[10px] uppercase tracking-wider
                            {{ $order->status === 'concluido' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' :
                               ($order->status === 'pago' ? 'bg-indigo-950 text-indigo-400 border border-indigo-800' :
                               'bg-amber-950 text-amber-400 border border-amber-800') }}">
                            {{ $order->status }}
                        </span>
                    </div>
                </div>

                <div class="py-4 divide-y divide-slate-800/50">
                    @foreach($order->items as $item)
                        <div class="py-2 flex justify-between items-center text-xs">
                            <span class="text-slate-300">{{ $item->listing->title }}</span>
                            <span class="font-semibold text-slate-200">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4 border-t border-slate-800 flex justify-between items-center">
                    <div>
                        <span class="text-xs text-slate-400">Total:</span>
                        <span class="font-extrabold text-sm text-indigo-400 ml-1">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</span>
                    </div>
                    <a href="{{ route('orders.show', $order) }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs transition">
                        Ver Detalhes &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-3xl bg-slate-900 border border-slate-800 p-12 text-center text-slate-500 text-sm">
                Você ainda não realizou nenhum pedido.
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $orders->links() }}
    </div>
</div>
@endsection
