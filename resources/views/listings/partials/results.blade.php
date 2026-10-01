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
        <x-empty-state title="Nenhum anúncio encontrado para os filtros selecionados." description="Tente outra busca ou explore o catálogo completo para encontrar seu próximo upgrade." icon="search" :href="route('listings.index')" action="Explorar todos os anúncios" />
    @endforelse
</div>
{{ $listings->links('vendor.pagination.catalog') }}
