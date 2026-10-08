@extends('layouts.app')

@section('title', 'Meu Carrinho')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5 mb-8">
        <x-page-heading title="Meu Carrinho de Compras" eyebrow="SEU PRÓXIMO UPGRADE" description="Confira os itens que vão fazer parte da sua próxima conquista." />
        @if($cart && $cart->items->isNotEmpty())
            <form action="{{ route('cart.clear') }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 font-semibold transition">
                    Esvaziar Carrinho
                </button>
            </form>
        @endif
    </div>

    @if(!$cart || $cart->items->isEmpty())
        <x-empty-state title="Seu carrinho está vazio" description="Nenhum cosmético ou serviço adicionado até agora." icon="cart" :href="route('listings.index')" action="Explorar Anúncios" />
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="md:col-span-2 space-y-4">
                @foreach($cart->items as $item)
                    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center text-xl">
                                💎
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-white">{{ $item->listing->title }}</h3>
                                <span class="text-xs text-slate-400">Vendedor: {{ $item->listing->seller->name }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <span class="font-extrabold text-sm text-brand-400">
                                R$ {{ number_format($item->unit_price, 2, ',', '.') }}
                            </span>
                            <form action="{{ route('cart.remove', $item) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-500 hover:text-rose-400 text-sm transition" title="Remover">
                                    ✕
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Resumo do Pedido -->
            <div>
                <div class="rounded-xl bg-slate-900 border border-slate-800 p-6">
                    <h3 class="font-bold text-sm text-white mb-4">Resumo do Pedido</h3>
                    <div class="flex justify-between text-xs text-slate-400 mb-2">
                        <span>Quantidade de Itens</span>
                        <span>{{ $cart->items->count() }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-extrabold text-white pt-4 border-t border-slate-800 mb-6">
                        <span>Total</span>
                        <span class="text-brand-400 text-lg">R$ {{ number_format($cart->total(), 2, ',', '.') }}</span>
                    </div>

                    <a href="{{ route('checkout.index') }}" class="w-full py-3.5 bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold rounded-xl shadow-lg shadow-brand-600/10 transition text-center block text-sm">
                        Avançar para Checkout
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
