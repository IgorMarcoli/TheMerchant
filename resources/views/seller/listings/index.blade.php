@extends('layouts.app')

@section('title', 'Painel do Vendedor - Meus Anúncios')

@section('content')
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold text-white flex items-center gap-2">
            <span>💼</span> Meus Anúncios
        </h1>
        <p class="text-xs text-slate-400">Gerencie seus produtos e serviços cadastrados.</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('seller.sales.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition">
            Ver Vendas
        </a>
        @can('create', \App\Models\Listing::class)
        <a href="{{ route('seller.anuncios.create') }}" class="px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-slate-950 text-xs font-bold shadow-lg shadow-brand-600/10 transition">
            + Novo Anúncio
        </a>
        @endcan
    </div>
</div>

<div class="space-y-4">
    @forelse($listings as $listing)
        <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center text-xl">
                    💎
                </div>
                <div>
                    <h3 class="font-bold text-sm text-white">{{ $listing->title }}</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-slate-400">{{ $listing->game->name }} &bull; {{ $listing->category->name }}</span>
                        <span class="text-xs text-brand-400 font-bold ml-2">R$ {{ number_format($listing->price, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider
                    {{ $listing->status === 'publicado' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' :
                       ($listing->status === 'pausado' ? 'bg-amber-950 text-amber-400 border border-amber-800' :
                       'bg-slate-800 text-slate-400') }}">
                    {{ $listing->status }}
                </span>

                @can('update', $listing)
                @if (in_array($listing->status, ['publicado', 'pausado'], true))
                <form action="{{ route('seller.anuncios.status', $listing) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-xs text-slate-300 hover:text-white px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 transition">
                        {{ $listing->status === 'publicado' ? 'Pausar' : 'Ativar' }}
                    </button>
                </form>

                @endif
                @endcan
                @can('delete', $listing)
                <form action="{{ route('seller.anuncios.destroy', $listing) }}" method="POST" onsubmit="return confirm('Deseja realmente excluir este anúncio?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 px-3 py-1.5 rounded-lg bg-rose-950/40 border border-rose-800/40 transition">
                        Excluir
                    </button>
                </form>
                @endcan
            </div>
        </div>
    @empty
        <div class="rounded-3xl bg-slate-900 border border-slate-800 p-12 text-center text-slate-500 text-sm">
            Você ainda não cadastrou nenhum anúncio.
        </div>
    @endforelse
</div>

<div class="mt-8">
    {{ $listings->links() }}
</div>
@endsection
