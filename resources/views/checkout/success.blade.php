@extends('layouts.app')

@php
    $paymentStatus = strtolower($order->payment?->status ?? 'pending');
    $isPaid = $order->isPaid();
    $paymentLabels = [
        'approved' => 'Pagamento confirmado', 'paid' => 'Pagamento confirmado',
        'pending' => 'Aguardando pagamento', 'in_process' => 'Pagamento em análise',
        'payment.created' => 'Aguardando pagamento', 'rejected' => 'Pagamento recusado',
        'payment.rejected' => 'Pagamento recusado', 'expired' => 'Pagamento expirado',
        'payment.expired' => 'Pagamento expirado', 'cancelled' => 'Pagamento cancelado',
        'payment.cancelled' => 'Pagamento cancelado', 'canceled' => 'Pagamento cancelado',
    ];
    $statusLabel = $order->status === 'cancelado'
        ? 'Pedido cancelado'
        : ($isPaid ? 'Pagamento confirmado' : ($order->reservation_expires_at?->isPast()
            ? 'Reserva expirada; confirme o status do pedido'
            : ($paymentLabels[$paymentStatus] ?? 'Aguardando confirmação do pagamento')));
@endphp

@section('title', 'Status do Pedido')

@section('content')
<div class="max-w-2xl mx-auto py-10">
    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-7 sm:p-9 shadow-xl">
        <div class="w-16 h-16 rounded-full {{ $isPaid ? 'bg-emerald-950 text-emerald-400 border-emerald-700' : 'bg-amber-950 text-amber-300 border-amber-700' }} border text-2xl flex items-center justify-center mb-6">
            {{ $isPaid ? '✓' : '⏳' }}
        </div>
        <x-page-heading :title="$isPaid ? 'Pagamento confirmado' : ($order->status === 'cancelado' ? 'Pedido cancelado' : 'Pedido criado')" eyebrow="STATUS DA COMPRA" />
        <p class="text-sm text-slate-300 mt-3">
            {{ $isPaid ? 'O gateway confirmou o pagamento. Os vendedores já podem iniciar as entregas.' : ($order->status === 'cancelado' ? 'Este pedido foi cancelado. Consulte o acompanhamento para ver o status registrado pelo gateway.' : 'Seu pedido foi registrado e aguarda confirmação do gateway. Acessar esta página não confirma o pagamento.') }}
        </p>

        <div class="rounded-xl bg-slate-950 border border-slate-800 p-5 mt-6 text-sm space-y-3">
            <div class="flex justify-between gap-4"><span class="text-slate-500">Número do pedido</span><strong class="font-mono text-brand-400">{{ $order->order_number }}</strong></div>
            <div class="flex justify-between gap-4"><span class="text-slate-500">Pedido</span><strong class="text-slate-200">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</strong></div>
            <div class="flex justify-between gap-4"><span class="text-slate-500">Pagamento</span><strong class="{{ $isPaid ? 'text-emerald-400' : 'text-amber-300' }}">{{ $statusLabel }}</strong></div>
            <div class="flex justify-between gap-4"><span class="text-slate-500">Total</span><strong class="text-white">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</strong></div>
            @if($order->reservation_expires_at && ! $isPaid && $order->status !== 'cancelado')
                <div class="flex justify-between gap-4"><span class="text-slate-500">{{ $order->reservation_expires_at->isPast() ? 'Reserva expirou em' : 'Reserva válida até' }}</span><strong class="text-slate-200">{{ $order->reservation_expires_at->format('d/m/Y H:i') }} ({{ config('app.timezone') }})</strong></div>
            @endif
        </div>

        @if(in_array($paymentStatus, ['rejected', 'expired', 'cancelled', 'canceled']) || $order->status === 'cancelado')
            <p class="text-sm text-rose-300 mt-5">O pagamento não foi concluído. Consulte seu pedido para verificar o status atualizado e tente novamente se a opção estiver disponível.</p>
        @endif

        <div class="flex flex-wrap gap-3 mt-7">
            <a href="{{ route('orders.show', $order) }}" class="px-5 py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold text-sm">Acompanhar pedido</a>
            @if(! $isPaid && $order->status !== 'cancelado')
                <a href="{{ route('checkout.cancel', $order) }}" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-sm">Precisa de ajuda?</a>
            @endif
            <a href="{{ route('listings.index') }}" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-sm">Continuar navegando</a>
        </div>
    </div>
</div>
@endsection
