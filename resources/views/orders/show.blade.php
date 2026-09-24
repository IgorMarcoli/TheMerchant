@extends('layouts.app')

@section('title', "Pedido #{$order->order_number}")

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('orders.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1">
            &larr; Voltar aos Pedidos
        </a>
        <span class="px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider
            {{ $order->status === 'concluido' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' :
               ($order->status === 'pago' ? 'bg-brand-950 text-brand-400 border border-brand-800' :
               'bg-amber-950 text-amber-400 border border-amber-800') }}">
            Status: {{ $order->status }}
        </span>
    </div>

    <div class="rounded-3xl bg-slate-900 border border-slate-800 p-8 mb-8 shadow-xl">
        <div class="flex justify-between items-start pb-6 border-b border-slate-800">
            <div>
                <h1 class="text-xl font-bold text-white mb-1">Pedido #{{ $order->order_number }}</h1>
                <span class="text-xs text-slate-400">Realizado em {{ $order->created_at->format('d/m/Y \à\s H:i') }}</span>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-400 block">Total do Pedido</span>
                <span class="text-2xl font-black text-brand-400">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</span>
            </div>
        </div>

        <!-- Itens do Pedido & Avaliação pós-compra (RF15) -->
        <div class="divide-y divide-slate-800 my-6">
            @foreach($order->items as $item)
                <div class="py-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-center text-xl">
                            💎
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-white">{{ $item->listing->title }}</h3>
                            <span class="text-xs text-slate-400">Vendedor: {{ $item->seller->name }}</span>
                            <div class="mt-1">
                                <span class="text-[10px] uppercase tracking-wider font-semibold px-2 py-0.5 rounded bg-slate-950 text-slate-300 border border-slate-800">
                                    Entrega: {{ $item->delivery_status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col md:items-end gap-2">
                        <span class="font-extrabold text-sm text-brand-400">
                            R$ {{ number_format($item->unit_price, 2, ',', '.') }}
                        </span>

                        <div class="flex items-center gap-3">
                            <form action="{{ route('chat.start') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="listing_id" value="{{ $item->listing_id }}">
                                <button type="submit" class="text-xs text-brand-400 hover:text-brand-300 font-semibold flex items-center gap-1 transition">
                                    <span>💬</span> Falar com vendedor
                                </button>
                            </form>

                            <!-- Se entregue e ainda não avaliado -->
                            @if($item->delivery_status === 'entregue' && !$item->review)
                                <button type="button" onclick="document.getElementById('review-form-{{ $item->id }}').classList.toggle('hidden')" class="text-xs text-amber-400 hover:text-amber-300 font-semibold underline">
                                    ★ Avaliar Vendedor
                                </button>
                            @elseif($item->review)
                                <span class="text-xs text-amber-400">
                                    ★ Avaliado com nota {{ $item->review->rating }}/5
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Formulário de Avaliação -->
                @if($item->delivery_status === 'entregue' && !$item->review)
                    <div id="review-form-{{ $item->id }}" class="hidden p-4 rounded-2xl bg-slate-950 border border-slate-800 my-2">
                        <form action="{{ route('orders.review.store', [$order, $item]) }}" method="POST">
                            @csrf
                            <h4 class="text-xs font-bold text-white mb-2">Avaliar atendimento de {{ $item->seller->name }}</h4>
                            <div class="flex items-center gap-4 mb-3">
                                <label class="text-xs text-slate-400">Nota:</label>
                                <select name="rating" class="bg-slate-900 border border-slate-700 text-xs rounded-lg px-2 py-1 text-white">
                                    <option value="5">5 - Excelente</option>
                                    <option value="4">4 - Muito Bom</option>
                                    <option value="3">3 - Regular</option>
                                    <option value="2">2 - Ruim</option>
                                    <option value="1">1 - Péssimo</option>
                                </select>
                            </div>
                            <textarea name="comment" rows="2" placeholder="Deixe um comentário sobre a agilidade e entrega..." class="w-full bg-slate-900 border border-slate-700 text-xs rounded-xl p-2.5 text-white mb-3"></textarea>
                            <button type="submit" class="px-4 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs">
                                Publicar Avaliação
                            </button>
                        </form>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endsection
