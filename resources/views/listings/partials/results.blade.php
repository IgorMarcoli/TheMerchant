<p class="text-sm text-slate-300">{{ $listings->total() }} anúncio(s) encontrado(s)</p>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    @forelse($listings as $listing)
        <div class="space-y-2">
            <x-listing-card :listing="$listing" />
            <p class="text-xs text-slate-400">
                @if($listing->seller->sellerProfile->total_reviews > 0)
                    Vendedor: {{ number_format($listing->seller->sellerProfile->reputation_score, 2, ',', '.') }} / 5
                    ({{ $listing->seller->sellerProfile->total_reviews }} avaliações)
                @else
                    Vendedor sem avaliações
                @endif
            </p>
        </div>
    @empty
        <p class="col-span-full py-12 text-center text-slate-400">Nenhum anúncio encontrado para os filtros selecionados.</p>
    @endforelse
</div>
{{ $listings->links('vendor.pagination.catalog') }}
