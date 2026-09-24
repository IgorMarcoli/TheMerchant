@extends('layouts.app')

@section('title', $listing->title)

@section('content')
<div class="mb-6">
    <a href="{{ route('listings.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1">
        &larr; Voltar ao Catálogo
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna da Galeria e Detalhes -->
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-3xl bg-slate-900 border border-slate-800 p-8 flex items-center justify-center min-h-[350px] relative overflow-hidden">
            <span class="text-7xl">💎</span>
            <div class="absolute top-4 left-4 flex gap-2">
                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-slate-950/80 text-white border border-slate-700">
                    {{ $listing->game->name }}
                </span>
                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-brand-950/80 text-brand-400 border border-brand-800">
                    {{ $listing->category->name }}
                </span>
            </div>
        </div>

        <div class="rounded-3xl bg-slate-900 border border-slate-800 p-8">
            <h2 class="text-lg font-bold text-white mb-4">Descrição do Produto / Serviço</h2>
            <div class="prose prose-invert max-w-none text-slate-300 text-sm leading-relaxed whitespace-pre-line">
                {{ $listing->description }}
            </div>
        </div>
    </div>

    <!-- Coluna de Compra e Dados do Vendedor -->
    <div class="space-y-6">
        <!-- Card de Preço e Ação -->
        <div class="rounded-3xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
            <span class="text-xs text-slate-400 block mb-1">Preço à vista</span>
            <div class="text-3xl font-black text-brand-400 mb-6">
                R$ {{ number_format($listing->price, 2, ',', '.') }}
            </div>

            @if($listing->isAvailable())
                <form action="{{ route('cart.add', $listing) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-3.5 bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold rounded-xl shadow-lg shadow-brand-600/10 transition flex items-center justify-center gap-2">
                        <span>🛒</span> Adicionar ao Carrinho
                    </button>
                </form>
            @else
                <div class="w-full py-3 text-center rounded-xl bg-slate-800 text-slate-400 font-bold text-sm">
                    Item Indisponível ({{ ucfirst($listing->status) }})
                </div>
            @endif

            @guest
                <a href="{{ route('login') }}" class="w-full mt-3 py-3 rounded-xl border border-slate-700 bg-slate-800/80 hover:bg-slate-800 text-slate-200 font-semibold text-xs transition flex items-center justify-center gap-2">
                    <span>💬</span> Falar com vendedor
                </a>
            @else
                @if(auth()->id() === $listing->seller_id)
                    <div class="w-full mt-3 py-2.5 rounded-xl bg-slate-950/60 border border-slate-800 text-slate-500 font-medium text-xs text-center">
                        <span>👤</span> Seu próprio anúncio
                    </div>
                @else
                    <form action="{{ route('chat.start') }}" method="POST" class="mt-3">
                        @csrf
                        <input type="hidden" name="listing_id" value="{{ $listing->id }}">
                        <button type="submit" class="w-full py-3 rounded-xl border border-brand-500/40 bg-brand-950/40 hover:bg-brand-900/40 text-brand-300 font-bold text-xs transition flex items-center justify-center gap-2 shadow-sm">
                            <span>💬</span> Falar com vendedor
                        </button>
                    </form>
                @endif
            @endguest

            <p class="text-[11px] text-slate-400 text-center mt-4">
                🛡️ Transação protegida com garantia de entrega TheMerchant.
            </p>
        </div>

        <!-- Card do Vendedor (RF08) -->
        <div class="rounded-3xl bg-slate-900 border border-slate-800 p-6">
            <h3 class="text-xs uppercase tracking-wider font-bold text-slate-400 mb-4">Informações do Vendedor</h3>
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-xl font-bold text-slate-950">
                    {{ substr($listing->seller->name, 0, 1) }}
                </div>
                <div>
                    <h4 class="font-bold text-sm text-white">{{ $listing->seller->name }}</h4>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-amber-400 text-xs font-bold">★ {{ $listing->seller->sellerProfile?->reputation_score ?? '5.00' }}</span>
                        <span class="text-slate-500 text-xs">({{ $listing->seller->sellerProfile?->total_reviews ?? 0 }} avaliações)</span>
                    </div>
                </div>
            </div>

            @if($listing->seller->sellerProfile?->bio)
                <p class="text-xs text-slate-400 leading-relaxed bg-slate-950 p-3 rounded-xl border border-slate-800 mb-4">
                    {{ $listing->seller->sellerProfile->bio }}
                </p>
            @endif

            <!-- Denúncia -->
            @auth
                <button type="button" onclick="document.getElementById('report-modal').classList.toggle('hidden')" class="w-full text-xs text-rose-400 hover:text-rose-300 transition text-center mt-2">
                    🚩 Denunciar este anúncio
                </button>
            @endauth
        </div>
    </div>
</div>

<!-- Modal de Denúncia (RF16) -->
@auth
<div id="report-modal" class="hidden fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6">
        <h3 class="text-base font-bold text-white mb-2">Denunciar Anúncio</h3>
        <p class="text-xs text-slate-400 mb-4">Informe o motivo da denúncia para a equipe de moderação analisar.</p>

        <form action="{{ route('listings.report', $listing) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Motivo</label>
                    <select name="reason" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="Item inexistente ou fraude">Item inexistente ou tentativa de golpe</option>
                        <option value="Violação de termos do jogo">Violação de termos de uso do jogo</option>
                        <option value="Preço abusivo ou anúncio duplicado">Preço abusivo ou anúncio duplicado</option>
                        <option value="Outros">Outro motivo</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Detalhes e Evidências</label>
                    <textarea name="details" rows="3" required placeholder="Descreva os fatos..." class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-xs text-white"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('report-modal').classList.add('hidden')" class="px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white rounded-xl">Enviar Denúncia</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endauth
@endsection
