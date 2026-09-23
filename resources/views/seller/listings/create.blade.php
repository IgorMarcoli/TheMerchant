@extends('layouts.app')

@section('title', 'Criar Novo Anúncio')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('seller.anuncios.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1">
            &larr; Voltar aos Meus Anúncios
        </a>
    </div>

    <div class="rounded-3xl bg-slate-900 border border-slate-800 p-8 shadow-xl">
        <h1 class="text-xl font-bold text-white mb-2">Publicar Novo Anúncio</h1>
        <p class="text-xs text-slate-400 mb-6">Preencha as informações do item ou serviço digital.</p>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-rose-950/80 border border-rose-500/40 text-rose-300 text-xs">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('seller.anuncios.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Jogo correspondente</label>
                    <select name="game_id" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="">Selecione um jogo...</option>
                        @foreach($games as $game)
                            <option value="{{ $game->id }}" {{ old('game_id') == $game->id ? 'selected' : '' }}>{{ $game->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Categoria</label>
                    <select name="category_id" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="">Selecione a categoria...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Título do Anúncio</label>
                <input type="text" name="title" value="{{ old('title') }}" required placeholder="Ex: Karambit Fade FN 99% Fade" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Preço em Reais (R$)</label>
                <input type="number" step="0.01" min="1.00" name="price" value="{{ old('price') }}" required placeholder="Ex: 250.00" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Descrição e Condições de Entrega</label>
                <textarea name="description" rows="4" required placeholder="Detalhes de conservação, float, disponibilidade de horário para serviços..." class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-xs text-white">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Imagens do Produto (Mínimo 1, Máximo 6)</label>
                <input type="file" name="images[]" multiple required accept="image/*" class="w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-600 file:text-slate-950 hover:file:bg-brand-500 cursor-pointer">
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold rounded-xl shadow-lg shadow-brand-600/10 text-xs transition">
                    Publicar Anúncio Agora
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
