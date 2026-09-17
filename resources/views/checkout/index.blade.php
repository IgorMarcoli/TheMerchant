@extends('layouts.app')

@section('title', 'Finalizar Compra')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-white mb-2">Finalização do Pedido</h1>
        <p class="text-xs text-slate-400">Ambiente protegido com pagamento via Gateway oficial.</p>
    </div>

    <div class="rounded-3xl bg-slate-900 border border-slate-800 p-8 shadow-2xl">
        <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-6">Itens do Pedido</h2>
        <div class="divide-y divide-slate-800 mb-6">
            @foreach($cart->items as $item)
                <div class="py-3 flex justify-between items-center text-sm">
                    <span class="text-slate-200">{{ $item->listing->title }}</span>
                    <span class="font-bold text-indigo-400">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        <div class="pt-4 border-t border-slate-800 flex justify-between items-center mb-8">
            <span class="text-sm font-semibold text-slate-400">Valor Total a Pagar</span>
            <span class="text-2xl font-black text-indigo-400">R$ {{ number_format($cart->total(), 2, ',', '.') }}</span>
        </div>

        <form action="{{ route('checkout.process') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Instruções para o Vendedor / Trade URL (Opcional)</label>
                <textarea name="notes" rows="2" placeholder="Ex: Steam Trade Link ou horário de disponibilidade para coaching" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-indigo-500"></textarea>
            </div>

            <div class="flex items-start gap-3 p-4 rounded-xl bg-slate-950 border border-slate-800">
                <input type="checkbox" name="terms_agreed" id="terms" required class="mt-1 rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                <label for="terms" class="text-xs text-slate-400">
                    Declaro estar ciente de que as negociações devem seguir as políticas das plataformas de jogos e que meus dados de pagamento serão processados de forma segura pelo gateway credenciado.
                </label>
            </div>

            <button type="submit" class="w-full py-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition text-sm flex items-center justify-center gap-2">
                <span>🔒</span> Confirmar Pedido e Pagar
            </button>
        </form>
    </div>
</div>
@endsection
