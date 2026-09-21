@extends('layouts.app')
@section('title', 'Seu próximo nível começa aqui')
@section('body-class', 'tm-home')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
    <meta name="description" content="Explore skins, cosméticos e coaching para seus jogos favoritos. Compre e venda no TheMerchant, o marketplace feito para quem joga.">
@endpush
@section('navigation')
    <x-storefront-header />
@endsection
@section('content')
<div class="tm-welcome"><span><span class="tm-status-dot"></span> DE PLAYER PARA PLAYER</span><span>Seu universo gamer. Um só lugar.</span></div>
<section class="tm-hero" aria-labelledby="hero-title">
    <div class="tm-hero-copy"><span class="tm-eyebrow"><x-icon name="spark" /> SEU INVENTÁRIO MERECE UM UPGRADE</span><h1 id="hero-title">Seu próximo nível<br>começa <span>aqui.</span></h1><p>Skins que marcam presença. Coaching que muda o jogo.<br class="tm-desktop-break"> Encontre o que falta para jogar do seu jeito.</p><div class="tm-hero-actions"><a class="tm-button" href="{{ route('listings.index') }}">Explorar marketplace <x-icon /></a><a class="tm-quiet-link" href="#jogos">Encontre seu jogo <x-icon name="chevron" /></a></div><div class="tm-hero-note"><x-icon name="game" /><span>Para quem vive o game, dentro e fora da partida.</span></div></div>
    <div class="tm-hero-visual" aria-hidden="true"><span class="tm-orbit tm-orbit-one"></span><span class="tm-orbit tm-orbit-two"></span><span class="tm-visual-coordinate">INVENTORY / 001</span><img src="{{ asset('images/marketplace/hero-blade.svg') }}" alt="" width="620" height="440" fetchpriority="high"><div class="tm-floating-label"><span class="tm-status-dot"></span> NOVAS POSSIBILIDADES. <strong>SEU ESTILO.</strong></div><span class="tm-visual-cross">+</span></div>
</section>
<div class="tm-benefits" aria-label="Explore o marketplace">
    <div><x-icon name="spark" /><span><strong>Um inventário com personalidade</strong><small>Skins, avatares e temas para você</small></span></div><div><x-icon name="game" /><span><strong>Sua próxima evolução</strong><small>Aprenda com sessões de coaching</small></span></div><div><x-icon name="store" /><span><strong>Conecte-se à comunidade</strong><small>Compre e venda entre jogadores</small></span></div>
</div>
<section class="tm-section" id="jogos" aria-labelledby="games-title">
    <div class="tm-section-heading"><div><span class="tm-kicker">ESCOLHA SEU UNIVERSO</span><h2 id="games-title">Qual é o seu jogo?</h2></div><a href="{{ route('listings.index') }}" class="tm-section-link">Explorar todos <x-icon /></a></div>
    <div class="tm-game-grid">
        @forelse ($games as $game)
            <a href="{{ route('listings.index', ['jogo' => $game->id]) }}" class="tm-game-card tm-game-{{ $loop->index % 6 }}">
                @if ($game->cover_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($game->cover_image))
                    <img src="{{ asset('storage/' . $game->cover_image) }}" alt="" loading="lazy" width="320" height="240">
                @elseif (in_array($game->slug, ['cs2', 'dota-2']))
                    <img src="{{ asset('images/marketplace/' . $game->slug . '.jpg') }}" alt="" loading="lazy" width="320" height="240">
                @else
                    <span class="tm-game-monogram" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($game->name, 0, 2)) }}</span>
                @endif
                <span class="tm-game-info"><strong>{{ $game->name }}</strong><small>{{ $game->listings_count }} {{ $game->listings_count === 1 ? 'anúncio disponível' : 'anúncios disponíveis' }}</small></span><span class="tm-game-arrow"><x-icon name="chevron" /></span>
            </a>
        @empty
            <div class="tm-empty">Os jogos estão chegando. Enquanto isso, conheça o catálogo e prepare seu próximo anúncio.</div>
        @endforelse
    </div>
</section>
<section class="tm-section" aria-labelledby="listings-title">
    <div class="tm-section-heading"><div><span class="tm-kicker">DESCUBRA SEU PRÓXIMO DROP</span><h2 id="listings-title">Acabaram de chegar <x-icon name="bolt" /></h2></div><a href="{{ route('listings.index') }}" class="tm-section-link">Ver todos os anúncios <x-icon /></a></div>
    <div class="tm-filter-links" aria-label="Categorias de anúncios"><a class="tm-filter-active" href="{{ route('listings.index') }}">Todos os anúncios</a><a href="{{ route('listings.index', ['tipo' => 'cosmetic']) }}">Skins e cosméticos</a><a href="{{ route('listings.index', ['tipo' => 'service']) }}">Coaching</a></div>
    <div class="tm-product-grid">@forelse ($listings as $listing)<x-storefront-listing :listing="$listing" />@empty<div class="tm-empty"><x-icon name="store" /><h3>O próximo drop pode ser seu.</h3><p>Ainda não há anúncios publicados. Seja um dos primeiros a fazer parte.</p><a class="tm-button" href="{{ route('seller.anuncios.create') }}">Criar meu anúncio <x-icon /></a></div>@endforelse</div>
</section>
<section class="tm-promo-grid" aria-label="Mais possibilidades">
    <a class="tm-promo tm-promo-coaching" href="{{ route('listings.index', ['tipo' => 'service']) }}"><div><span class="tm-kicker">MENOS GG EZ. MAIS EVOLUÇÃO.</span><h2>Seu melhor jogo<br>ainda está por vir.</h2><p>Encontre um coach e dê o próximo passo.</p><span class="tm-promo-link">Explorar coaching <x-icon /></span></div><x-icon name="game" class="tm-promo-icon" /></a>
    <a class="tm-promo tm-promo-seller" href="{{ route('seller.anuncios.create') }}"><div><span class="tm-kicker">SEU INVENTÁRIO TEM POTENCIAL</span><h2>Transforme seus itens<br>em novas conquistas.</h2><p>Abra espaço para o próximo upgrade.</p><span class="tm-promo-link">Começar a vender <x-icon /></span></div><x-icon name="store" class="tm-promo-icon" /></a>
</section>
<section class="tm-how tm-section" id="como-funciona" aria-labelledby="how-title"><div class="tm-section-heading"><div><span class="tm-kicker">FÁCIL COMO DAR PLAY</span><h2 id="how-title">Do drop ao próximo GG.</h2></div><span class="tm-how-caption">Encontre. Escolha. Acompanhe.</span></div><div class="tm-steps"><div><span>01</span><h3>Encontre seu upgrade</h3><p>Explore os jogos e filtre os anúncios pelo que você procura.</p></div><div><span>02</span><h3>Confira cada detalhe</h3><p>Veja a descrição do item ou serviço antes de adicionar ao carrinho.</p></div><div><span>03</span><h3>Acompanhe seu pedido</h3><p>Acesse sua conta para consultar a compra e o status da entrega.</p></div></div></section>
@endsection
