@extends('layouts.app')

@section('title', 'Catálogo de Anúncios')

@push('styles')
<script src="{{ asset('js/catalog-filters.js') }}"></script>
@endpush

@section('content')
<div x-data="catalogFilters" @popstate.window="restore()" class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-white mb-2">Explorar Catálogo</h1>
        <p class="text-sm text-slate-400">Encontre cosméticos raros e serviços digitais verificados.</p>
    </div>

    <form x-ref="form" method="GET" action="{{ route('listings.index') }}"
          @submit.prevent="search()" @input="changed($event)" @change="changed($event)"
          class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <div>
            <label for="catalog-search" class="block text-sm mb-2">Buscar por palavra-chave</label>
            <input id="catalog-search" type="search" name="busca" value="{{ $filters['busca'] ?? '' }}" maxlength="150"
                   placeholder="Ex: Karambit, Vandal, Coaching..." class="w-full min-h-11 px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-white">
        </div>
        <details class="group" @keydown.escape="$el.open = false; $el.querySelector('summary').focus()">
            <summary class="cursor-pointer min-h-11 flex items-center text-brand-400 font-semibold focus-visible:outline">
                Filtros e ordenação
            </summary>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-4">
                <div>
                    <label for="catalog-type" class="block text-sm mb-2">Tipo de anúncio</label>
                    <select id="catalog-type" name="tipo" class="w-full min-h-11 px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl">
                        <option value="">Todos os anúncios</option>
                        <option value="cosmetic" @selected(($filters['tipo'] ?? '') === 'cosmetic')>Skins e cosméticos</option>
                        <option value="service" @selected(($filters['tipo'] ?? '') === 'service')>Coaching e serviços</option>
                    </select>
                </div>
                <div>
                    <label for="catalog-game" class="block text-sm mb-2">Jogo</label>
                    <select id="catalog-game" name="jogo" class="w-full min-h-11 px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl">
                        <option value="">Todos os jogos</option>
                        @foreach($games as $game)
                            <option value="{{ $game->id }}" @selected(($filters['jogo'] ?? '') == $game->id)>{{ $game->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="catalog-category" class="block text-sm mb-2">Categoria</label>
                    <select id="catalog-category" name="categoria" class="w-full min-h-11 px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl">
                        <option value="">Todas as categorias</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['categoria'] ?? '') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                @foreach(['min' => 'mínimo', 'max' => 'máximo'] as $bound => $label)
                    <div>
                        <label for="price-{{ $bound }}" class="block text-sm mb-2">Preço {{ $label }} (R$)</label>
                        <input id="price-{{ $bound }}" name="preco_{{ $bound }}" type="number" min="0" max="99999999.99" step="0.01"
                               value="{{ $filters['preco_'.$bound] ?? '' }}" placeholder="Sem limite"
                               class="w-full min-h-11 px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl">
                        <input type="range" min="0" :max="rangeMax" step="0.01" value="{{ $filters['preco_'.$bound] ?? ($bound === 'min' ? 0 : 1000) }}"
                               data-price="{{ $bound }}" aria-label="Ajustar preço {{ $label }}"
                               class="w-full min-h-11 accent-brand-400">
                    </div>
                @endforeach
                <div>
                    <label for="catalog-rating" class="block text-sm mb-2">Nota mínima do vendedor</label>
                    <select id="catalog-rating" name="nota_min" class="w-full min-h-11 px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl">
                        <option value="">Todas, incluindo sem avaliações</option>
                        @foreach([1, 2, 3, 4, 5] as $rating)
                            <option value="{{ $rating }}" @selected(($filters['nota_min'] ?? '') == $rating)>{{ $rating }} estrela(s) ou mais</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="catalog-sort" class="block text-sm mb-2">Ordenar por</label>
                    <select id="catalog-sort" name="ordem" class="w-full min-h-11 px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-xl">
                        @foreach(['recentes' => 'Mais recentes', 'menor_preco' => 'Menor preço', 'maior_preco' => 'Maior preço', 'reputacao' => 'Reputação do vendedor'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['ordem'] ?? 'recentes') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </details>
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="min-h-11 px-5 rounded-xl bg-brand-600 text-slate-950 font-semibold">Filtrar resultados</button>
            <a href="{{ route('listings.index') }}" @click.prevent="clear()" class="min-h-11 inline-flex items-center px-4 rounded-xl border border-slate-600">Limpar todos</a>
        </div>
        <div class="flex flex-wrap gap-2" aria-label="Filtros ativos">
            <template x-for="tag in tags" :key="tag.name">
                <button type="button" @click="remove(tag.name)" :aria-label="'Remover filtro ' + tag.label"
                        class="min-h-11 px-3 rounded-xl border border-brand-700 text-sm text-brand-300">
                    <span x-text="tag.label"></span><span aria-hidden="true"> ×</span>
                </button>
            </template>
        </div>
    </form>

    <p x-cloak x-show="loading" role="status" class="text-brand-300">Carregando resultados…</p>
    <div x-cloak x-show="error" role="alert" class="p-4 border border-rose-700 rounded-xl text-rose-300">
        <span x-text="error"></span>
        <button type="button" @click="search()" class="underline min-h-11 px-3">Tentar novamente</button>
    </div>
    <section x-ref="results" id="catalog-results" aria-live="polite" :aria-busy="loading"
             @click="paginate($event)" class="space-y-6">
        @include('listings.partials.results')
    </section>
</div>
@endsection
