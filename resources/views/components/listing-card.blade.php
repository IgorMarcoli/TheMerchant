@props(['listing'])

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
