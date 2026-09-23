@props(['listing'])
<a class="tm-product" href="{{ route('listings.show', $listing->slug) }}">
    <div class="tm-product-art {{ $listing->category?->type === 'service' ? 'tm-product-service' : '' }}">
        @if ($listing->primaryImage && \Illuminate\Support\Facades\Storage::disk('public')->exists($listing->primaryImage->image_path))
            <img src="{{ asset('storage/' . $listing->primaryImage->image_path) }}" alt="{{ $listing->title }}" width="400" height="240" loading="lazy">
        @else
            <x-icon :name="$listing->category?->type === 'service' ? 'game' : 'spark'" class="tm-product-symbol" /><span class="tm-art-caption">{{ $listing->category?->type === 'service' ? 'COACHING' : 'COSMÉTICOS' }}</span>
        @endif
        <span class="tm-product-tag">{{ $listing->game?->name }}</span>
    </div>
    <div class="tm-product-body"><span class="tm-product-category">{{ $listing->category?->name }}</span><h3>{{ $listing->title }}</h3><div class="tm-product-bottom"><div><span class="tm-price-label">Preço do anúncio</span><strong>R$ {{ number_format($listing->price, 2, ',', '.') }}</strong></div><span class="tm-product-arrow"><x-icon /></span></div></div>
</a>
