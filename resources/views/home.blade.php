@extends('layouts.app')
@section('title', 'Skins e coaching entre jogadores')
@section('body-class', 'tm-home')
@push('styles')
    <meta name="description" content="Explore skins, cosméticos e coaching para seus jogos favoritos. Compre e venda no TheMerchant, o marketplace feito para quem joga.">
@endpush
@section('content')
<div class="tm-welcome"><span><span class="tm-status-dot"></span> Marketplace de skins e coaching</span><span>Pagamento por Pix e entrega combinada pelo chat</span></div>
<section class="tm-hero" aria-labelledby="hero-title">
    <div class="tm-hero-copy"><span class="tm-eyebrow"><x-icon name="store" /> Compra e venda entre jogadores</span><h1 id="hero-title">Skins e coaching<br>direto de <span>outros jogadores</span></h1><p>Encontre aquela skin que faltava ou alguém para te ajudar a subir de elo.<br class="tm-desktop-break"> Você paga por Pix e combina a entrega com o vendedor pelo chat.</p><div class="tm-hero-actions"><a class="tm-button" href="{{ route('listings.index') }}">Explorar marketplace <x-icon /></a><a class="tm-quiet-link" href="#jogos">Encontre seu jogo <x-icon name="chevron" /></a></div><div class="tm-hero-note"><x-icon name="game" /><span>Cada pedido fica salvo na sua conta, do pagamento até a entrega.</span></div></div>
    <div class="tm-hero-visual" aria-hidden="true"><span class="tm-orbit tm-orbit-one"></span><span class="tm-orbit tm-orbit-two"></span><span class="tm-visual-coordinate">INVENTORY / 001</span><img src="{{ asset('images/marketplace/hero-blade.svg') }}" alt="" width="620" height="440" fetchpriority="high"><div class="tm-floating-label"><span class="tm-status-dot"></span> Escolha, pague e <strong>receba pelo chat</strong></div><span class="tm-visual-cross">+</span></div>
</section>
<div class="tm-benefits" aria-label="Explore o marketplace">
    <div><x-icon name="gem" /><span><strong>Skins e cosméticos</strong><small>Para deixar o inventário do seu jeito</small></span></div><div><x-icon name="game" /><span><strong>Coaching</strong><small>Aulas com quem já chegou onde você quer chegar</small></span></div><div><x-icon name="store" /><span><strong>Venda o que não usa</strong><small>Anuncie e converse direto com quem quer comprar</small></span></div>
</div>
<section class="tm-section" id="jogos" aria-labelledby="games-title">
    <div class="tm-section-heading"><div><span class="tm-kicker">Jogos</span><h2 id="games-title">Qual é o seu jogo?</h2></div><a href="{{ route('listings.index') }}" class="tm-section-link">Explorar todos <x-icon /></a></div>
    <div class="tm-game-grid">
        @forelse ($games as $game)
            @php
                $bundledCover = file_exists(public_path('images/marketplace/' . $game->slug . '.jpg')) ? asset('images/marketplace/' . $game->slug . '.jpg') : null;
                $cover = $game->cover_image_url ?? $bundledCover;
            @endphp
            <a href="{{ route('listings.index', ['jogo' => $game->id]) }}" class="tm-game-card tm-game-{{ $loop->index % 6 }}">
                @if ($cover)
                    {{-- A capa é decorativa (o nome já está no card). Se falhar, tenta a capa local e depois o monograma. --}}
                    <img src="{{ $cover }}" alt="" loading="lazy" width="320" height="240"
                         @if ($bundledCover && $bundledCover !== $cover) data-fallback="{{ $bundledCover }}" @endif
                         onerror="if (this.dataset.fallback) { this.src = this.dataset.fallback; delete this.dataset.fallback; } else { this.nextElementSibling.hidden = false; this.remove(); }">
                @endif
                <span class="tm-game-monogram" aria-hidden="true" @if ($cover) hidden @endif>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($game->name, 0, 2)) }}</span>
                <span class="tm-game-info"><strong>{{ $game->name }}</strong><small>{{ $game->listings_count }} {{ $game->listings_count === 1 ? 'anúncio disponível' : 'anúncios disponíveis' }}</small></span><span class="tm-game-arrow"><x-icon name="chevron" /></span>
            </a>
        @empty
            <div class="tm-empty">Nenhum jogo cadastrado ainda. Enquanto isso, dá uma olhada no catálogo.</div>
        @endforelse
    </div>
</section>
<section class="tm-section" aria-labelledby="listings-title">
    <div class="tm-section-heading"><div><span class="tm-kicker">Novidades</span><h2 id="listings-title">Acabaram de chegar <x-icon name="bolt" /></h2></div><a href="{{ route('listings.index') }}" class="tm-section-link">Ver todos os anúncios <x-icon /></a></div>
    <div class="tm-filter-links" aria-label="Categorias de anúncios"><a class="tm-filter-active" href="{{ route('listings.index') }}">Todos os anúncios</a><a href="{{ route('listings.index', ['tipo' => 'cosmetic']) }}">Skins e cosméticos</a><a href="{{ route('listings.index', ['tipo' => 'service']) }}">Coaching</a></div>
    <div class="tm-product-grid">@forelse ($listings as $listing)<x-storefront-listing :listing="$listing" />@empty<div class="tm-empty"><x-icon name="store" /><h3>Ainda não tem nenhum anúncio por aqui</h3><p>Que tal ser a primeira pessoa a vender?</p><a class="tm-button" href="{{ route('seller.application') }}">Criar meu anúncio <x-icon /></a></div>@endforelse</div>
</section>
<section class="tm-promo-grid" aria-label="Mais possibilidades">
    <a class="tm-promo tm-promo-coaching" href="{{ route('listings.index', ['tipo' => 'service']) }}"><div><span class="tm-kicker">Coaching</span><h2>Travou no elo?<br>Peça ajuda a quem já passou</h2><p>Aulas individuais, análise de partida e mentoria por jogo.</p><span class="tm-promo-link">Explorar coaching <x-icon /></span></div><x-icon name="game" class="tm-promo-icon" /></a>
    <a class="tm-promo tm-promo-seller" href="{{ route('seller.application') }}"><div><span class="tm-kicker">Venda</span><h2>Tem item parado<br>no inventário?</h2><p>Crie um anúncio e converse direto com quem quer comprar.</p><span class="tm-promo-link">Começar a vender <x-icon /></span></div><x-icon name="store" class="tm-promo-icon" /></a>
</section>
<section class="tm-how tm-section" id="como-funciona" aria-labelledby="how-title"><div class="tm-section-heading"><div><span class="tm-kicker">Como funciona</span><h2 id="how-title">Comprar aqui leva três passos</h2></div><span class="tm-how-caption">Do anúncio até a entrega</span></div><div class="tm-steps"><div><span>01</span><h3>Encontre o que procura</h3><p>Explore os jogos e filtre os anúncios pelo que você procura.</p></div><div><span>02</span><h3>Confira cada detalhe</h3><p>Veja a descrição do item ou serviço antes de adicionar ao carrinho.</p></div><div><span>03</span><h3>Acompanhe seu pedido</h3><p>Acesse sua conta para consultar a compra e o status da entrega.</p></div></div></section>
@endsection
