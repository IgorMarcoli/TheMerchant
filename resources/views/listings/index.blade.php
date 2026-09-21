@extends('layouts.app')

@section('title', 'Catálogo de Anúncios')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white mb-2">Explorar Catálogo</h1>
    <p class="text-sm text-slate-400">Encontre cosméticos raros e serviços digitais verificados.</p>
</div>

<!-- Filtros e Busca (RF07) -->
<form method="GET" action="{{ route('listings.index') }}" class="p-6 rounded-2xl bg-slate-900 border border-slate-800 mb-8">
    <div class="mb-4">
        <label for="catalog-type" class="block text-xs font-semibold text-slate-300 mb-2">Tipo de anúncio</label>
        <select id="catalog-type" name="tipo" class="bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white">
            <option value="">Todos os anúncios</option>
            <option value="cosmetic" @selected(request('tipo') === 'cosmetic')>Skins e cosméticos</option>
            <option value="service" @selected(request('tipo') === 'service')>Coaching e serviços</option>
        </select>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Busca Textual -->
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-slate-300 mb-2">Buscar por palavra-chave</label>
            <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Ex: Karambit, Vandal, Coaching..." class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-brand-500">
        </div>

        <!-- Filtro por Jogo -->
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-2">Jogo</label>
            <select name="jogo" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500">
                <option value="">Todos os Jogos</option>
                @foreach($games as $game)
                    <option value="{{ $game->id }}" {{ request('jogo') == $game->id ? 'selected' : '' }}>{{ $game->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Filtro por Categoria -->
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-2">Categoria</label>
            <select name="categoria" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500">
                <option value="">Todas</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('categoria') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Botão Filtrar -->
        <div class="flex items-end">
            <button type="submit" class="w-full py-2.5 bg-brand-600 hover:bg-brand-500 text-slate-950 text-sm font-semibold rounded-xl transition shadow-lg shadow-brand-600/10">
                Filtrar Resultados
            </button>
        </div>
    </div>
</form>

<!-- Grid de Resultados -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    @forelse($listings as $listing)
        <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden hover:border-brand-500/40 hover:shadow-xl hover:shadow-brand-500/10 transition flex flex-col">
            <div class="h-44 bg-slate-950 relative flex items-center justify-center text-slate-700">
                <span class="text-4xl">💎</span>
                <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-900/90 text-slate-300 border border-slate-700">
                    {{ $listing->game->name }}
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
                        <span class="text-base font-extrabold text-brand-400">R$ {{ number_format($listing->price, 2, ',', '.') }}</span>
                    </div>
                    <a href="{{ route('listings.show', $listing->slug) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-brand-600 hover:bg-brand-500 text-slate-950 transition">
                        Ver Anúncio
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full text-center py-16 text-slate-500 text-sm">
            Nenhum anúncio encontrado para os filtros selecionados.
        </div>
    @endforelse
</div>

<!-- Paginação (RNF05) -->
<div class="mt-10">
    {{ $listings->links() }}
</div>
@endsection
