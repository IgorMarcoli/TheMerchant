@extends('layouts.app')

@php
    $payment = $order->payment;
    $paymentStatus = $payment?->status;
    $isPaid = $order->isPaid();
    $isCancelled = $order->status === 'cancelado';
    $isAwaitingPix = ! $isPaid && ! $isCancelled && $paymentStatus === 'pending' && $order->reservation_expires_at?->isFuture();
    $paymentLabels = [
        'pending' => 'Aguardando pagamento do PIX',
        'paid' => 'Pagamento confirmado',
        'expired' => 'PIX expirado',
        'cancelled' => 'Pagamento cancelado',
        'failed' => 'Pagamento não concluído',
        'refunded' => 'Pagamento estornado',
        'disputed' => 'Pagamento em contestação',
    ];
    $statusLabel = $isPaid
        ? 'Pagamento confirmado'
        : ($payment?->refund_status !== null
            ? 'Pago após o fim da reserva; estorno '.($payment->refund_status === 'refunded' ? 'concluído' : 'em processamento')
            : ($isCancelled
                ? ($paymentLabels[$paymentStatus] ?? 'Pedido cancelado')
                : ($payment === null ? 'Cobrança PIX ainda não gerada' : ($paymentLabels[$paymentStatus] ?? 'Aguardando confirmação do gateway'))));
@endphp

@section('title', 'Status do Pedido')

@section('content')
<div class="max-w-2xl mx-auto py-10">
    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-7 sm:p-9 shadow-xl">
        <div class="w-16 h-16 rounded-full {{ $isPaid ? 'bg-emerald-950 text-emerald-400 border-emerald-700' : 'bg-amber-950 text-amber-300 border-amber-700' }} border text-2xl flex items-center justify-center mb-6" aria-hidden="true">
            {{ $isPaid ? '✓' : '⏳' }}
        </div>
        <x-page-heading :title="$isPaid ? 'Pagamento confirmado' : ($isCancelled ? 'Pedido cancelado' : 'Pedido criado')" eyebrow="STATUS DA COMPRA" />
        <p class="text-sm text-slate-300 mt-3">
            {{ $isPaid ? 'O gateway confirmou o pagamento. Os vendedores já podem iniciar as entregas.' : ($isCancelled ? 'Este pedido foi cancelado e a reserva dos itens foi liberada.' : 'Seus itens estão reservados. A confirmação vem do gateway; acessar esta página não confirma o pagamento.') }}
        </p>

        <div class="rounded-xl bg-slate-950 border border-slate-800 p-5 mt-6 text-sm space-y-3">
            <div class="flex justify-between gap-4"><span class="text-slate-500">Número do pedido</span><strong class="font-mono text-brand-400">{{ $order->order_number }}</strong></div>
            <div class="flex justify-between gap-4"><span class="text-slate-500">Pedido</span><strong class="text-slate-200">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</strong></div>
            <div class="flex justify-between gap-4"><span class="text-slate-500">Pagamento</span><strong class="{{ $isPaid ? 'text-emerald-400' : 'text-amber-300' }}">{{ $statusLabel }}</strong></div>
            <div class="flex justify-between gap-4"><span class="text-slate-500">Total</span><strong class="text-white">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</strong></div>
            @if($order->reservation_expires_at && ! $isPaid && ! $isCancelled)
                <div class="flex justify-between gap-4"><span class="text-slate-500">{{ $order->reservation_expires_at->isPast() ? 'Reserva expirou em' : 'Reserva válida até' }}</span><strong class="text-slate-200">{{ $order->reservation_expires_at->format('d/m/Y H:i') }} ({{ config('app.timezone') }})</strong></div>
            @endif
        </div>

        @if($isAwaitingPix && auth()->user()->can('pay', $order))
            <div class="rounded-xl bg-slate-950 border border-brand-600/40 p-5 mt-6 text-sm" x-data="{ copied: false }">
                <h2 class="font-bold text-white mb-1">Pague com PIX</h2>
                <p class="text-xs text-slate-400 mb-4">Escaneie o QR Code ou use o código copia e cola no app do seu banco. O PIX vence às {{ ($payment->expires_at ?? $order->reservation_expires_at)->format('H:i') }}.</p>
                @if($payment->br_code_base64 && str_starts_with($payment->br_code_base64, 'data:image/png;base64,'))
                    <img src="{{ $payment->br_code_base64 }}" alt="QR Code PIX do pedido {{ $order->order_number }}" class="w-52 h-52 mx-auto rounded-lg bg-white p-2">
                @endif
                @if($payment->br_code)
                    <label for="pix-code" class="block text-xs font-semibold text-slate-300 mt-4 mb-1">PIX copia e cola</label>
                    <div class="flex gap-2">
                        <input id="pix-code" x-ref="code" type="text" readonly value="{{ $payment->br_code }}" class="flex-1 min-w-0 bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 font-mono">
                        <button type="button" @click="navigator.clipboard.writeText($refs.code.value).then(() => copied = true)" class="px-3 py-2 rounded-lg bg-brand-600 hover:bg-brand-500 text-slate-950 text-xs font-bold" x-text="copied ? 'Copiado!' : 'Copiar'">Copiar</button>
                    </div>
                @endif
                <div class="flex flex-wrap gap-3 mt-5">
                    <form method="POST" action="{{ route('checkout.payment.refresh', $order) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs">Já paguei — atualizar status</button>
                    </form>
                    @if(config('services.abacatepay.dev_mode') && $payment->dev_mode)
                        <form method="POST" action="{{ route('checkout.payment.simulate', $order) }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-xl bg-amber-900/60 hover:bg-amber-800 text-amber-200 font-semibold text-xs">Simular pagamento (modo dev)</button>
                        </form>
                    @endif
                </div>
            </div>
        @elseif($payment === null && ! $isPaid && ! $isCancelled && $order->reservation_expires_at?->isFuture() && auth()->user()->can('pay', $order))
            <div class="rounded-xl bg-slate-950 border border-amber-700/50 p-5 mt-6 text-sm">
                <p class="text-amber-200">O gateway não respondeu ao gerar o PIX. Sua reserva continua válida.</p>
                <form method="POST" action="{{ route('checkout.payment.retry', $order) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold text-xs">Gerar PIX novamente</button>
                </form>
            </div>
        @endif

        @if($isCancelled && ! $isPaid)
            <p class="text-sm text-rose-300 mt-5">O pagamento não foi concluído dentro do prazo da reserva. Monte o carrinho novamente para tentar outra vez.</p>
        @endif

        <div class="flex flex-wrap gap-3 mt-7">
            <a href="{{ route('orders.show', $order) }}" class="px-5 py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold text-sm">Acompanhar pedido</a>
            @if(! $isPaid && ! $isCancelled)
                <a href="{{ route('checkout.cancel', $order) }}" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-sm">Precisa de ajuda?</a>
            @endif
            <a href="{{ route('listings.index') }}" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-sm">Continuar navegando</a>
        </div>
    </div>
</div>
@endsection
