@extends('layouts.app')

@section('title', $listing->title)

@section('content')
@php
    $isAvailable = $listing->isAvailable();
    $isOwnListing = auth()->check() && auth()->id() === $listing->seller_id;
    $sellerProfile = $listing->seller->sellerProfile;
    $rating = (float) ($sellerProfile?->reputation_score ?? 0);
    $reportModalHasErrors = $errors->has('reason') || $errors->has('details');
@endphp

<div class="space-y-6" x-data="{ reportOpen: @js($reportModalHasErrors) }" @keydown.escape.window="reportOpen = false">
    <div>
        <a href="{{ route('listings.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1">
            &larr; Voltar ao Catálogo
        </a>
    </div>

    <x-page-heading :title="$listing->title" :eyebrow="$listing->game->name" :description="$listing->category->name" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <section class="rounded-xl bg-slate-900 border border-slate-800 p-4 sm:p-6" aria-label="Fotos do anúncio"
                     x-data="{ images: @js($galleryImages), current: 0, transitioning: false, changeImage(index) { this.transitioning = true; setTimeout(() => { this.current = index; this.transitioning = false }, 120) } }">
                <div class="relative overflow-hidden rounded-xl bg-slate-950 min-h-[300px] sm:min-h-[440px] flex items-center justify-center">
                    <template x-if="images.length > 0">
                        <img :src="images[current]" :class="transitioning ? 'opacity-40' : 'opacity-100'" alt="{{ $listing->title }}" width="1200" height="800" fetchpriority="high" class="max-h-[70vh] w-full object-contain transition-opacity duration-300">
                    </template>
                    @if($galleryImages->isEmpty())
                        <x-icon :name="$listing->category->type === 'service' ? 'game' : 'spark'" />
                    @endif

                    <div class="absolute top-4 left-4 flex flex-wrap gap-2">
                        <span class="px-3 py-1 rounded-xl text-xs font-bold bg-slate-950/90 text-white border border-slate-700">{{ $listing->game->name }}</span>
                        <span class="px-3 py-1 rounded-xl text-xs font-bold bg-brand-950/90 text-brand-300 border border-brand-800">{{ $listing->category->name }}</span>
                        <span class="px-3 py-1 rounded-xl text-xs font-bold bg-slate-950/90 text-slate-200 border border-slate-700">{{ $listing->category->type === 'service' ? 'Serviço' : 'Item digital' }}</span>
                    </div>

                    <button type="button" x-cloak x-show="images.length > 1" @click="changeImage((current - 1 + images.length) % images.length)" aria-label="Foto anterior" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-slate-950/80 text-white border border-slate-700 hover:bg-slate-800">‹</button>
                    <button type="button" x-cloak x-show="images.length > 1" @click="changeImage((current + 1) % images.length)" aria-label="Próxima foto" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-slate-950/80 text-white border border-slate-700 hover:bg-slate-800">›</button>
                    <span x-cloak x-show="images.length > 1" x-text="`${current + 1} / ${images.length}`" class="absolute bottom-3 right-3 rounded-lg bg-slate-950/80 px-2 py-1 text-xs text-white" aria-live="polite"></span>
                </div>

                @if($galleryImages->count() > 1)
                    <div class="flex gap-3 overflow-x-auto pt-4" aria-label="Selecionar foto">
                        @foreach($galleryImages as $index => $imageUrl)
                            <button type="button" @click="changeImage({{ $index }})" :aria-current="current === {{ $index }} ? 'true' : 'false'" aria-label="Ver foto {{ $index + 1 }}" class="shrink-0 rounded-lg border-2 border-transparent focus:border-brand-400" :class="current === {{ $index }} ? 'border-brand-400' : 'border-transparent'">
                                <img src="{{ $imageUrl }}" alt="" width="96" height="80" loading="lazy" class="w-24 h-20 object-cover rounded-md">
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-xl bg-slate-900 border border-slate-800 p-6 sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                    <h2 class="text-lg font-bold text-white">Descrição do {{ $listing->category->type === 'service' ? 'Serviço' : 'Anúncio' }}</h2>
                    <span class="text-xs text-slate-500" aria-label="{{ number_format($listing->views_count, 0, ',', '.') }} visualizações">{{ number_format($listing->views_count, 0, ',', '.') }} visualizações</span>
                </div>
                <div class="prose prose-invert max-w-none text-slate-300 text-sm leading-relaxed whitespace-pre-line">{{ $listing->description }}</div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-6 text-xs">
                    <div class="rounded-lg bg-slate-950 border border-slate-800 p-3"><dt class="text-slate-500">Jogo</dt><dd class="font-semibold text-slate-200 mt-1">{{ $listing->game->name }}</dd></div>
                    <div class="rounded-lg bg-slate-950 border border-slate-800 p-3"><dt class="text-slate-500">Categoria / tipo</dt><dd class="font-semibold text-slate-200 mt-1">{{ $listing->category->name }} · {{ $listing->category->type === 'service' ? 'Serviço' : 'Item digital' }}</dd></div>
                </dl>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-xl bg-slate-900 border border-slate-800 p-6 shadow-xl" aria-label="Compra do anúncio">
                <span class="text-xs text-slate-400 block mb-1">Preço</span>
                <div class="text-3xl font-black text-brand-400 mb-6">R$ {{ number_format($listing->price, 2, ',', '.') }}</div>

                @if($isAvailable && ! $isOwnListing)
                    <form action="{{ route('cart.add', $listing) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-3.5 bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold rounded-xl shadow-lg shadow-brand-600/10 transition flex items-center justify-center gap-2">
                            <span aria-hidden="true">🛒</span> Adicionar ao carrinho
                        </button>
                    </form>
                @elseif($isOwnListing)
                    <div class="w-full py-3 text-center rounded-xl bg-slate-800 text-slate-400 font-semibold text-sm">Este é seu anúncio</div>
                @else
                    <button type="button" disabled aria-disabled="true" class="w-full py-3 text-center rounded-xl bg-slate-800 text-slate-400 font-bold text-sm cursor-not-allowed">
                        Indisponível · {{ ucfirst(str_replace('_', ' ', $listing->status)) }}
                    </button>
                @endif

                @guest
                    <a href="{{ route('login') }}" class="w-full mt-3 py-3 rounded-xl border border-slate-700 bg-slate-800/80 hover:bg-slate-800 text-slate-200 font-semibold text-xs transition flex items-center justify-center gap-2">
                        Entrar para falar com o vendedor
                    </a>
                @else
                    @if(! $isOwnListing)
                        <form action="{{ route('chat.start') }}" method="POST" class="mt-3">
                            @csrf
                            <input type="hidden" name="listing_id" value="{{ $listing->id }}">
                            <button type="submit" class="w-full py-3 rounded-xl border border-brand-500/40 bg-brand-950/40 hover:bg-brand-900/40 text-brand-300 font-bold text-xs transition">Falar com o vendedor</button>
                        </form>
                    @endif
                @endguest

                <div class="mt-5 rounded-xl bg-slate-950 border border-slate-800 p-4">
                    <h3 class="text-xs font-bold text-slate-200 mb-1">Condições de entrega</h3>
                    <p class="text-[11px] leading-relaxed text-slate-400">
                        As condições específicas estão na descrição do anúncio. {{ $listing->category->type === 'service' ? 'Após a confirmação do pagamento, combine o horário e os detalhes da sessão com o vendedor.' : 'Após a confirmação do pagamento, acompanhe a entrega digital pelo pedido e alinhe os detalhes com o vendedor.' }}
                    </p>
                </div>
                <p class="text-[11px] text-slate-500 text-center mt-3">A disponibilidade é conferida novamente ao adicionar ao carrinho.</p>
            </section>

            <section class="rounded-xl bg-slate-900 border border-slate-800 p-6" aria-labelledby="seller-card-title">
                <h3 id="seller-card-title" class="text-xs uppercase tracking-wider font-bold text-slate-400 mb-4">Informações do vendedor</h3>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-xl font-bold text-slate-950" aria-hidden="true">{{ substr($listing->seller->name, 0, 1) }}</div>
                    <div>
                        <h4 class="font-bold text-sm text-white">{{ $listing->seller->name }}</h4>
                        <div class="mt-1" aria-label="{{ $sellerProfile?->total_reviews ? 'Avaliação média de '.number_format($rating, 2, ',', '.').' em 5, baseada em '.$sellerProfile->total_reviews.' avaliações' : 'Vendedor sem avaliações' }}">
                            @if(($sellerProfile?->total_reviews ?? 0) > 0)
                                <span class="text-amber-400 text-sm" aria-hidden="true">@for($star = 1; $star <= 5; $star++){{ $star <= round($rating) ? '★' : '☆' }}@endfor</span>
                                <span class="text-amber-300 text-xs font-bold">{{ number_format($rating, 2, ',', '.') }}/5</span>
                                <span class="text-slate-500 text-xs">({{ $sellerProfile->total_reviews }} avaliações)</span>
                            @else
                                <span class="text-slate-500 text-xs">Sem avaliações</span>
                            @endif
                        </div>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mb-4">Vendas concluídas: <strong class="text-white">{{ number_format($sellerProfile?->total_sales ?? 0, 0, ',', '.') }}</strong></p>
                @if($sellerProfile?->bio)
                    <p class="text-xs text-slate-400 leading-relaxed bg-slate-950 p-3 rounded-xl border border-slate-800">{{ $sellerProfile->bio }}</p>
                @endif

                @auth
                    @if(! $isOwnListing)
                        <button type="button" @click="reportOpen = true" class="w-full text-xs text-rose-400 hover:text-rose-300 transition text-center mt-4">🚩 Denunciar este anúncio</button>
                    @endif
                @endauth
            </section>
        </aside>
    </div>

    @auth
        @if(! $isOwnListing)
            <div x-cloak x-show="reportOpen" x-transition.opacity class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center z-50 p-4" role="presentation" @click.self="reportOpen = false">
                <section x-show="reportOpen" x-transition class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6" role="dialog" aria-modal="true" aria-labelledby="report-modal-title" @keydown.escape.stop="reportOpen = false">
                    <h3 id="report-modal-title" class="text-base font-bold text-white mb-2">Denunciar anúncio</h3>
                    <p class="text-xs text-slate-400 mb-4">Informe o motivo e descreva a situação para a equipe de moderação.</p>
                    <form action="{{ route('listings.report', $listing) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="report-reason" class="block text-xs font-semibold text-slate-300 mb-1">Motivo</label>
                            <select id="report-reason" name="reason" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                                <option value="fraude" @selected(old('reason') === 'fraude')>Item inexistente ou tentativa de golpe</option>
                                <option value="termos" @selected(old('reason') === 'termos')>Violação dos termos do jogo</option>
                                <option value="preco" @selected(old('reason') === 'preco')>Preço abusivo ou anúncio duplicado</option>
                                <option value="Outro" @selected(old('reason') === 'Outro')>Outro motivo</option>
                            </select>
                            @error('reason')<p class="text-xs text-rose-400 mt-1" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="report-details" class="block text-xs font-semibold text-slate-300 mb-1">Detalhes e evidências</label>
                            <textarea id="report-details" name="details" rows="4" minlength="10" maxlength="1000" required placeholder="Descreva os fatos..." class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-xs text-white">{{ old('details') }}</textarea>
                            @error('details')<p class="text-xs text-rose-400 mt-1" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="reportOpen = false" class="px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white rounded-xl">Enviar denúncia</button>
                        </div>
                    </form>
                </section>
            </div>
        @endif
    @endauth
</div>
@endsection
