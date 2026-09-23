@extends('layouts.app')

@section('title', 'Pedido Realizado')

@section('content')
<div class="max-w-xl mx-auto text-center py-12">
    <div class="w-20 h-20 rounded-full bg-emerald-950/80 border border-emerald-500/30 text-emerald-400 text-3xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-emerald-950">
        ✓
    </div>
    <h1 class="text-3xl font-black text-white mb-2">Pedido Registrado com Sucesso!</h1>
    <p class="text-sm text-slate-400 mb-6">
        Número do Pedido: <span class="font-mono text-brand-400 font-bold">{{ $order->order_number }}</span>
    </p>

    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-6 text-left mb-8 text-xs text-slate-300 space-y-2">
        <div class="flex justify-between">
            <span class="text-slate-500">Status atual:</span>
            <span class="font-bold text-amber-400 uppercase tracking-wider">{{ $order->status }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-slate-500">Total:</span>
            <span class="font-bold text-white">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</span>
        </div>
    </div>

    <div class="flex justify-center gap-4">
        <a href="{{ route('orders.show', $order) }}" class="px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold text-xs shadow-lg shadow-brand-600/10 transition">
            Acompanhar Pedido
        </a>
        <a href="{{ route('listings.index') }}" class="px-6 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition">
            Continuar Navegando
        </a>
    </div>
</div>
@endsection
