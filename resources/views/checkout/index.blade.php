@extends('layouts.app')

@section('title', 'Finalizar Compra')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8 text-center">
        <x-page-heading title="Finalização do Pedido" eyebrow="FALTA POUCO PARA O PRÓXIMO NÍVEL" description="Confira os itens e as instruções de entrega antes de continuar." />
    </div>

    <div class="rounded-xl bg-slate-900 border border-slate-800 p-8 shadow-xl">
        <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-6">Itens do Pedido</h2>
        <div class="divide-y divide-slate-800 mb-6">
            @foreach($cart->items as $item)
                <div class="py-4 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-200">{{ $item->listing->title }} <span class="text-slate-500">× {{ $item->quantity }}</span></span>
                        <span class="font-bold text-brand-400">R$ {{ number_format($item->unit_price * $item->quantity, 2, ',', '.') }}</span>
                    </div>
                    <label for="item-instructions-{{ $item->id }}" class="block text-xs text-slate-400 mt-3 mb-1">
                        {{ $item->listing->category?->type === 'service' ? 'Preferência de horário, fuso horário e detalhes para o coaching' : 'Instruções de entrega / Trade URL' }} (opcional)
                    </label>
                    <textarea id="item-instructions-{{ $item->id }}" name="item_instructions[{{ $item->id }}]" form="checkout-form" rows="2" maxlength="1000" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-brand-500">{{ old('item_instructions.'.$item->id) }}</textarea>
                    @error('item_instructions.'.$item->id)<p class="text-xs text-rose-400 mt-1">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>

        <div class="pt-4 border-t border-slate-800 flex justify-between items-center mb-8">
            <span class="text-sm font-semibold text-slate-400">Valor Total a Pagar</span>
            <span class="text-2xl font-black text-brand-400">R$ {{ number_format($cart->total(), 2, ',', '.') }}</span>
        </div>

        <form id="checkout-form" action="{{ route('checkout.process') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Instruções para o Vendedor / Trade URL (Opcional)</label>
                <textarea name="notes" rows="2" maxlength="500" placeholder="Observações gerais para o pedido" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-brand-500">{{ old('notes') }}</textarea>
                @error('notes')<p class="text-xs text-rose-400 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-start gap-3 p-4 rounded-xl bg-slate-950 border border-slate-800">
                <input type="checkbox" name="terms_agreed" id="terms" value="1" required @checked(old('terms_agreed')) class="mt-1 rounded bg-slate-900 border-slate-700 text-brand-600 focus:ring-brand-500">
                <label for="terms" class="text-xs text-slate-400">
                    Declaro estar ciente de que as negociações devem seguir as políticas das plataformas de jogos e que meus dados de pagamento serão processados de forma segura pelo gateway credenciado.
                </label>
            </div>
            @error('terms_agreed')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror
            @if($errors->any())
                <div role="alert" class="p-3 rounded-xl bg-rose-950/50 border border-rose-800 text-sm text-rose-300">Revise os campos destacados antes de continuar.</div>
            @endif

            <button type="submit" class="w-full py-4 bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold rounded-xl shadow-lg shadow-brand-600/15 transition text-sm flex items-center justify-center gap-2">
                <span>🔒</span> Confirmar Pedido e Pagar
            </button>
        </form>
    </div>
</div>
@endsection
