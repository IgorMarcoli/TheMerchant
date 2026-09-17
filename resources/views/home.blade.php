@extends('layouts.app')

@section('title', 'Início - O Maior Marketplace Gamer')

@section('content')
<!-- Hero Section -->
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950/80 via-slate-900 to-purple-950/60 border border-indigo-500/20 p-8 sm:p-14 mb-12 shadow-2xl">
    <div class="max-w-2xl">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 mb-6">
            ✨ Plataforma Segura com Gateway e Reputação
        </span>
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight mb-6">
            Compre e venda cosméticos e serviços com <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">total segurança</span>.
        </h1>
        <p class="text-slate-300 text-base sm:text-lg mb-8 leading-relaxed">
            Acabe com os riscos de negociações informais em fóruns e redes sociais. No TheMerchant, os pagamentos são protegidos, as transações são auditadas e os vendedores possuem reputação verificada.
        </p>
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('listings.index') }}" class="px-6 py-3 rounded-xl font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/40 transition">
                Explorar Anúncios
            </a>
            @guest
                <a href="{{ route('register') }}" class="px-6 py-3 rounded-xl font-semibold bg-slate-800/80 hover:bg-slate-800 text-slate-200 border border-slate-700 transition">
                    Comece a Vender
                </a>
            @endguest
        </div>
    </div>
</div>

<!-- Featured Games -->
<div class="mb-14">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
            <span>🎯</span> Jogos em Destaque
        </h2>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach($featuredGames as $game)
            <a href="{{ route('listings.index', ['jogo' => $game->id]) }}" class="group p-5 rounded-2xl bg-slate-900 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-850 transition flex flex-col items-center text-center">
                <div class="w-14 h-14 rounded-2xl bg-indigo-950/60 border border-indigo-500/20 flex items-center justify-center text-2xl group-hover:scale-110 transition mb-3">
                    🎮
                </div>
                <h3 class="font-bold text-sm text-white group-hover:text-indigo-400 transition">{{ $game->name }}</h3>
                <span class="text-xs text-slate-400 mt-1">{{ $game->listings_count }} anúncios</span>
            </a>
        @endforeach
    </div>
</div>

<!-- Recent Listings -->
<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
            <span>🔥</span> Anúncios Recentes
        </h2>
        <a href="{{ route('listings.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">Ver todos &rarr;</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($recentListings as $listing)
            <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden hover:border-indigo-500/40 hover:shadow-xl hover:shadow-indigo-500/10 transition flex flex-col">
                <div class="h-44 bg-slate-950 relative flex items-center justify-center text-slate-700">
                    <span class="text-4xl">💎</span>
                    <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-900/90 text-slate-300 border border-slate-700">
                        {{ $listing->game->name }}
                    </span>
                    <span class="absolute top-3 right-3 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-indigo-950 text-indigo-400 border border-indigo-800">
                        {{ $listing->category->type === 'cosmetic' ? 'Item' : 'Serviço' }}
                    </span>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <span class="text-xs text-slate-400">{{ $listing->category->name }}</span>
                        <h3 class="font-bold text-sm text-white mt-1 line-clamp-2">{{ $listing->title }}</h3>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Preço</span>
                            <span class="text-base font-extrabold text-indigo-400">R$ {{ number_format($listing->price, 2, ',', '.') }}</span>
                        </div>
                        <a href="{{ route('listings.show', $listing->slug) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white transition">
                            Detalhes
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-slate-500 text-sm">
                Nenhum anúncio disponível no momento.
            </div>
        @endforelse
    </div>
</div>
@endsection
