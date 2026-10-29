@extends('layouts.app')

@section('title', 'Meu Carrinho')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
    <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <x-page-heading title="Seu carrinho" eyebrow="QUASE LÁ" description="Confira seus anúncios antes de seguir para o checkout." />
        @if($cart && $cart->items->isNotEmpty())
            <div x-data="{ confirmClear: false }" class="shrink-0">
                <button type="button" @click="confirmClear = true" class="rounded-lg px-3 py-2 text-sm font-semibold text-rose-300 transition hover:bg-rose-400/10 hover:text-rose-200">
                    Esvaziar carrinho
                </button>
                <div x-cloak x-show="confirmClear" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4" @keydown.escape.window="confirmClear = false" @click.self="confirmClear = false" role="dialog" aria-modal="true" aria-labelledby="clear-cart-title">
                    <div class="w-full max-w-sm rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl">
                        <h2 id="clear-cart-title" class="text-lg font-bold text-white">Esvaziar seu carrinho?</h2>
                        <p class="mt-2 text-sm text-slate-400">Todos os anúncios serão removidos. Você pode adicioná-los novamente depois.</p>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" @click="confirmClear = false" class="rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800">Voltar</button>
                            <form action="{{ route('cart.clear') }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-bold text-white hover:bg-rose-400">Esvaziar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if($errors->any())
        <div role="alert" class="mb-6 rounded-xl border border-rose-500/40 bg-rose-950/80 p-4 text-sm text-rose-300">
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!$cart || $cart->items->isEmpty())
        <div class="rounded-3xl border border-slate-800 bg-slate-900/70 px-6 py-14 text-center sm:py-20">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-brand-400/20 bg-brand-400/10 text-3xl text-brand-300">🛒</div>
            <p class="mt-6 text-xs font-bold uppercase tracking-[0.2em] text-brand-300">Ainda dá tempo de encontrar algo incrível</p>
            <h2 class="mt-2 text-2xl font-black text-white">Seu carrinho está vazio</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-400">Explore os anúncios e adicione cosméticos ou serviços para continuar.</p>
            <a href="{{ route('listings.index') }}" class="mt-7 inline-flex items-center justify-center rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-brand-400">Explorar anúncios</a>
        </div>
    @else
        @php($hasUnavailableItems = $cart->hasUnavailableItems())
        @if($hasUnavailableItems)
            <div role="alert" class="mb-6 rounded-xl border border-amber-500/40 bg-amber-950/80 p-4 text-sm text-amber-300">
                Alguns anúncios do seu carrinho sofreram alterações de disponibilidade ou capacidade. Ajuste ou remova os itens sinalizados antes de avançar para o checkout.
            </div>
        @endif

        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
            <section aria-labelledby="cart-items-title" class="space-y-4">
                <div class="flex items-center justify-between px-1">
                    <h2 id="cart-items-title" class="text-sm font-bold text-white">Seus anúncios</h2>
                    <span class="text-xs text-slate-400">{{ $cart->items->count() }} {{ $cart->items->count() === 1 ? 'anúncio' : 'anúncios' }}</span>
                </div>
                @foreach($cart->items as $item)
                    @php($listing = $item->listing)
                    @php($unavailableReason = $item->unavailableReason())
                    <article class="rounded-2xl border {{ $unavailableReason ? 'border-amber-500/40 bg-slate-900/90' : 'border-slate-800 bg-slate-900' }} p-4 sm:p-5">
                        <div class="flex gap-4">
                            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950 sm:h-24 sm:w-24">
                                @if($listing && $listing->primaryImage)
                                    <img src="{{ $listing->primaryImage->url }}" alt="Imagem de {{ $listing->title }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-2xl" aria-hidden="true">{{ $listing?->category?->type === 'service' ? '🎮' : '💎' }}</div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-brand-300">{{ $listing?->game?->name }} · {{ $listing?->category?->name }}</p>
                                        <h3 class="mt-1 truncate text-sm font-bold text-white sm:text-base">
                                            @if($listing && $listing->isAvailable())
                                                <a href="{{ route('listings.show', $listing->slug) }}" class="transition hover:text-brand-300">{{ $listing->title }}</a>
                                            @else
                                                {{ $listing?->title ?? 'Anúncio indisponível' }}
                                            @endif
                                        </h3>
                                        <p class="mt-1 text-xs text-slate-400">Vendido por <span class="text-slate-300">{{ $listing?->seller?->name }}</span></p>
                                    </div>
                                    <form action="{{ route('cart.remove', $item) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Remover {{ $listing?->title }} do carrinho" class="rounded-lg p-2 text-slate-500 transition hover:bg-rose-400/10 hover:text-rose-300">Remover</button>
                                    </form>
                                </div>

                                @if($unavailableReason)
                                    <div role="alert" class="mt-3 rounded-lg border border-amber-500/30 bg-amber-950/60 px-3 py-2 text-xs text-amber-300">
                                        {{ $unavailableReason }}
                                    </div>
                                @elseif($item->hasPriceChanged())
                                    <div role="status" class="mt-3 rounded-lg border border-sky-500/30 bg-sky-950/60 px-3 py-2 text-xs text-sky-300">
                                        O preço atual deste anúncio no catálogo é R$ {{ number_format((float) $listing->price, 2, ',', '.') }}.
                                    </div>
                                @endif

                                <div class="mt-4 flex flex-wrap items-end justify-between gap-3 border-t border-slate-800 pt-3">
                                    @if($listing?->category?->type === 'service')
                                        <form action="{{ route('cart.update', $item) }}" method="POST" class="flex flex-wrap items-center gap-3">
                                            @csrf
                                            @method('PATCH')
                                            <label for="quantity-{{ $item->id }}" class="text-xs text-slate-400">Sessões</label>
                                            <select id="quantity-{{ $item->id }}" name="quantity" onchange="this.form.submit()" @disabled(!$listing->isAvailable()) class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400 disabled:cursor-not-allowed disabled:opacity-50">
                                                @for($quantity = 1; $quantity <= max(1, (int) $listing->capacity); $quantity++)
                                                    <option value="{{ $quantity }}" @selected($item->quantity === $quantity)>{{ $quantity }}</option>
                                                @endfor
                                            </select>
                                            <span class="text-xs text-slate-500">{{ $listing->session_duration_minutes }} min cada · {{ $listing->capacity }} {{ $listing->capacity === 1 ? 'disponível' : 'disponíveis' }}</span>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">Quantidade <span class="ml-1 font-semibold text-slate-200">1</span> <span class="ml-1 text-slate-500">(item único)</span></span>
                                    @endif
                                    <div class="text-right">
                                        <p class="text-[11px] text-slate-500">{{ $item->quantity > 1 ? 'R$ '.number_format((float) $item->unit_price, 2, ',', '.') . ' por sessão · subtotal' : 'Subtotal' }}</p>
                                        <p class="font-extrabold text-brand-300">R$ {{ number_format($item->subtotal(), 2, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
                <a href="{{ route('listings.index') }}" class="inline-flex items-center gap-2 px-1 py-2 text-sm font-semibold text-slate-400 transition hover:text-brand-300">← Continuar explorando</a>
            </section>

            <aside class="lg:sticky lg:top-6" aria-labelledby="cart-summary-title">
                <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
                    <h2 id="cart-summary-title" class="text-base font-bold text-white">Resumo do pedido</h2>
                    <div class="mt-5 space-y-3">
                        @foreach($cart->items as $item)
                            <div class="flex justify-between gap-4 text-xs">
                                <span class="min-w-0 truncate text-slate-400">{{ $item->listing?->title }}{{ $item->quantity > 1 ? ' × '.$item->quantity : '' }}</span>
                                <span class="shrink-0 text-slate-300">R$ {{ number_format($item->subtotal(), 2, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 flex justify-between border-t border-slate-800 pt-5 text-sm font-bold text-white">
                        <span>Total</span>
                        <span class="text-lg text-brand-300">R$ {{ number_format($cart->total(), 2, ',', '.') }}</span>
                    </div>
                    <p class="mt-2 text-[11px] text-slate-500">O total será confirmado novamente no checkout.</p>
                    @if($hasUnavailableItems)
                        <button type="button" disabled aria-disabled="true" class="mt-6 block w-full cursor-not-allowed rounded-xl bg-slate-800 px-4 py-3.5 text-center text-sm font-bold text-slate-400">
                            Ajuste os itens indisponíveis
                        </button>
                    @else
                        <a href="{{ route('checkout.index') }}" class="mt-6 block w-full rounded-xl bg-brand-500 px-4 py-3.5 text-center text-sm font-extrabold text-slate-950 shadow-lg shadow-brand-500/10 transition hover:bg-brand-400">Ir para o checkout <span aria-hidden="true">→</span></a>
                    @endif
                    <p class="mt-3 text-center text-[11px] text-slate-500">Compra protegida · Pagamento seguro</p>
                </div>
            </aside>
        </div>
    @endif
</div>
@endsection
