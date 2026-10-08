@extends('layouts.app')

@section('title', 'Pagamento não concluído')

@section('content')
<div class="max-w-2xl mx-auto py-10">
    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-7 sm:p-9 shadow-xl">
        <div class="w-16 h-16 rounded-full bg-rose-950 text-rose-300 border border-rose-800 text-2xl flex items-center justify-center mb-6">!</div>
        <x-page-heading title="Pagamento não concluído" eyebrow="RETORNO DO CHECKOUT" />
        <p class="text-sm text-slate-300 mt-3">O gateway retornou sem concluir o pagamento. O status exibido abaixo vem do pedido e das notificações recebidas; esta página não cancela nem altera o pedido.</p>

        <div class="rounded-xl bg-slate-950 border border-slate-800 p-5 mt-6 text-sm space-y-3">
            <div class="flex justify-between gap-4"><span class="text-slate-500">Número do pedido</span><strong class="font-mono text-brand-400">{{ $order->order_number }}</strong></div>
            <div class="flex justify-between gap-4"><span class="text-slate-500">Status do pedido</span><strong class="text-slate-200">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</strong></div>
            <div class="flex justify-between gap-4"><span class="text-slate-500">Status do pagamento</span><strong class="text-amber-300">{{ $order->payment?->status ?? 'Aguardando retorno do gateway' }}</strong></div>
            <div class="flex justify-between gap-4"><span class="text-slate-500">Total</span><strong class="text-white">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</strong></div>
        </div>

        <p class="text-xs text-slate-400 mt-5">Confira os dados no provedor de pagamento. Se o pagamento foi aprovado, aguarde a atualização automática ou consulte o acompanhamento do pedido antes de tentar novamente.</p>
        <div class="flex flex-wrap gap-3 mt-7">
            <a href="{{ route('orders.show', $order) }}" class="px-5 py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold text-sm">Ver acompanhamento</a>
            <a href="{{ route('orders.index') }}" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-sm">Meus pedidos</a>
        </div>
    </div>
</div>
@endsection
