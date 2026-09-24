@extends('layouts.app')

@section('title', 'Gestão de Categorias e Jogos')

@section('content')
<div class="max-w-7xl mx-auto space-y-8" x-data="{ editingCategory: null }">
    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="text-xs text-slate-400 hover:text-white transition">&larr; Painel Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-xs text-brand-400 font-semibold">Catálogo</span>
            </div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>🏷️</span> Gestão de Categorias e Jogos
            </h1>
            <p class="text-xs text-slate-400">
                Cadastre e inative categorias e jogos com integridade referencial protegida (RF18).
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="#form-nova-categoria" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold text-xs transition flex items-center gap-1.5 shadow-lg shadow-brand-600/10">
                <span>➕</span> Nova Categoria
            </a>
        </div>
    </div>

    <!-- Grid: Formulário de Cadastro + Gestão Rápida de Jogos -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Card 1: Criar Nova Categoria (2 colunas) -->
        <div id="form-nova-categoria" class="lg:col-span-2 rounded-3xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
            <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                <span>✨</span> Cadastrar Nova Categoria
            </h2>

            <form action="{{ route('admin.categorias.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-300 mb-1">Nome da Categoria</label>
                        <input type="text" name="name" id="name" required placeholder="Ex: Skins de Rifles, Coaching..."
                               value="{{ old('name') }}"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        @error('name')
                            <span class="text-rose-400 text-[10px] mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="game_id" class="block text-xs font-semibold text-slate-300 mb-1">Jogo Vinculado (RN01)</label>
                        <select name="game_id" id="game_id" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">Selecione um jogo...</option>
                            @foreach($games as $game)
                                <option value="{{ $game->id }}" @selected(old('game_id') == $game->id)>
                                    {{ $game->name }} {{ $game->active ? '' : '(Inativo)' }}
                                </option>
                            @endforeach
                        </select>
                        @error('game_id')
                            <span class="text-rose-400 text-[10px] mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="type" class="block text-xs font-semibold text-slate-300 mb-1">Tipo da Categoria</label>
                        <select name="type" id="type" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="cosmetic" @selected(old('type') === 'cosmetic')>Cosmético (Skin, Item, Visual)</option>
                            <option value="service" @selected(old('type') === 'service')>Serviço (Coaching, Mentoria)</option>
                        </select>
                        @error('type')
                            <span class="text-rose-400 text-[10px] mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center pt-6">
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                            <input type="checkbox" name="is_active" value="1" checked
                                   class="rounded bg-slate-950 border-slate-700 text-brand-500 focus:ring-brand-500">
                            <span>Categoria Ativa para novos anúncios</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold text-xs transition shadow-lg shadow-brand-600/10">
                        Salvar Categoria
                    </button>
                </div>
            </form>
        </div>

        <!-- Card 2: Controle de Jogos (Ativação / Inativação) -->
        <div class="rounded-3xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
            <h2 class="text-base font-bold text-white mb-2 flex items-center gap-2">
                <span>🎮</span> Jogos no Sistema
            </h2>
            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                Inative jogos para ocultá-los do catálogo público mantendo o histórico de pedidos seguro.
            </p>

            <div class="space-y-3 max-h-[260px] overflow-y-auto pr-1">
                @foreach($games as $game)
                    <div class="p-3 rounded-2xl bg-slate-950/80 border border-slate-800 flex items-center justify-between gap-3 text-xs">
                        <div>
                            <span class="font-bold text-white block">{{ $game->name }}</span>
                            <span class="text-[10px] {{ $game->active ? 'text-emerald-400 font-semibold' : 'text-slate-500' }}">
                                {{ $game->active ? '● Ativo no Catálogo' : '○ Inativo' }}
                            </span>
                        </div>

                        <form action="{{ route('admin.games.status', $game) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-2.5 py-1 rounded-lg text-[10px] font-bold transition border
                                {{ $game->active ? 'bg-slate-800 hover:bg-slate-700 text-amber-300 border-slate-700' : 'bg-emerald-950/80 hover:bg-emerald-900/80 text-emerald-300 border-emerald-800' }}">
                                {{ $game->active ? 'Inativar' : 'Ativar' }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Filtros de Busca -->
    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <select name="game_id" aria-label="Filtrar por jogo" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                <option value="">Todos os Jogos</option>
                @foreach($games as $g)
                    <option value="{{ $g->id }}" @selected(request('game_id') == $g->id)>{{ $g->name }}</option>
                @endforeach
            </select>

            <select name="type" aria-label="Filtrar por tipo" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                <option value="">Todos os Tipos</option>
                <option value="cosmetic" @selected(request('type') === 'cosmetic')>Cosméticos</option>
                <option value="service" @selected(request('type') === 'service')>Serviços</option>
            </select>

            <select name="status" aria-label="Filtrar por status" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                <option value="">Todos os Status</option>
                <option value="active" @selected(request('status') === 'active')>Ativas</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inativas</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition">
                Filtrar
            </button>
            @if(request()->hasAny(['game_id', 'type', 'status']))
                <a href="{{ route('admin.categorias.index') }}" class="text-xs text-slate-400 hover:text-white transition">Limpar</a>
            @endif
        </form>
    </div>

    <!-- Tabela de Categorias -->
    <div class="rounded-3xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
        <div class="p-6 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>📋</span> Categorias Cadastradas
            </h2>
            <span class="text-xs text-slate-400 font-semibold">{{ $categories->total() }} categorias encontradas</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-6">Categoria</th>
                        <th class="py-3.5 px-6">Jogo</th>
                        <th class="py-3.5 px-6">Tipo</th>
                        <th class="py-3.5 px-6">Anúncios</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($categories as $category)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-4 px-6 font-bold text-white">
                                {{ $category->name }}
                                <span class="block text-[10px] text-slate-500 font-mono font-normal">{{ $category->slug }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-slate-950 border border-slate-800 text-slate-300">
                                    {{ $category->game?->name ?? 'Geral' }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold
                                    {{ $category->type === 'cosmetic' ? 'bg-indigo-950 text-indigo-400 border border-indigo-800/40' : 'bg-emerald-950 text-emerald-400 border border-emerald-800/40' }}">
                                    {{ $category->type === 'cosmetic' ? 'Cosmético' : 'Serviço' }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="font-mono font-bold text-slate-200">{{ $category->listings_count }}</span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold
                                    {{ $category->is_active ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/40' : 'bg-slate-800 text-slate-400' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $category->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                    {{ $category->is_active ? 'Ativa' : 'Inativa' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Alternar Ativa / Inativa -->
                                    <form action="{{ route('admin.categorias.status', $category) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-[10px] font-semibold transition border
                                            {{ $category->is_active ? 'bg-slate-800 hover:bg-slate-700 text-amber-300 border-slate-700' : 'bg-emerald-950 hover:bg-emerald-900 text-emerald-300 border-emerald-800' }}"
                                            title="{{ $category->is_active ? 'Inativar categoria para novos anúncios' : 'Reativar categoria' }}">
                                            {{ $category->is_active ? 'Inativar' : 'Ativar' }}
                                        </button>
                                    </form>

                                    <!-- Excluir (Protegido se houver anúncios vinculados) -->
                                    <form action="{{ route('admin.categorias.destroy', $category) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded-lg text-[10px] font-semibold text-rose-400 hover:text-rose-300 bg-rose-950/40 hover:bg-rose-900/40 border border-rose-900/50 transition"
                                                title="{{ $category->listings_count > 0 ? 'Possui anúncios: exclusão será bloqueada para proteger integridade' : 'Excluir categoria vazia' }}">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                Nenhuma categoria cadastrada com os filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-6 border-t border-slate-800">
            {{ $categories->links() }}
        </div>
    </div>
</div>
@endsection

