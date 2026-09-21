<header class="tm-header">
    <div class="tm-container tm-topbar">
        <a href="{{ route('home') }}" class="tm-logo" aria-label="TheMerchant — início"><span class="tm-logomark"><x-icon name="store" /></span><span>the<span class="tm-accent">merchant</span>.</span></a>
        <form action="{{ route('listings.index') }}" method="GET" class="tm-search" role="search">
            <label for="home-search" class="sr-only">Buscar itens ou serviços</label><x-icon name="search" />
            <input id="home-search" name="busca" type="search" placeholder="O que você procura para o seu próximo GG?" maxlength="150">
            <button type="submit" aria-label="Buscar"><x-icon /></button>
        </form>
        <div class="tm-account">
            <a class="tm-cart" href="{{ route('cart.index') }}" aria-label="Meu carrinho"><x-icon name="cart" /></a>
            @auth
                <a href="{{ route('profile.edit') }}" class="tm-login"><x-icon name="user" /><span>Minha conta</span></a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="tm-button tm-button-small" type="submit">Sair</button></form>
            @else
                <a href="{{ route('login') }}" class="tm-login">Entrar</a><a href="{{ route('register') }}" class="tm-button tm-button-small">Criar conta</a>
            @endauth
        </div>
    </div>
    <nav class="tm-subnav" aria-label="Navegação do marketplace"><div class="tm-container tm-nav-inner">
        <div class="tm-nav-links">
            <a href="{{ route('listings.index') }}"><x-icon name="grid" />Explorar catálogo</a>
            <a href="{{ route('listings.index', ['tipo' => 'cosmetic']) }}">Skins e cosméticos</a>
            <a href="{{ route('listings.index', ['tipo' => 'service']) }}">Coaching <span class="tm-mini-label">LEVEL UP</span></a>
            <a href="#como-funciona">Como funciona</a>
        </div>
        <a class="tm-sell-link" href="{{ route('seller.anuncios.create') }}"><x-icon name="store" />Quero vender <x-icon /></a>
        <a class="tm-mobile-cart" href="{{ route('cart.index') }}"><x-icon name="cart" />Meu carrinho</a>
    </div></nav>
</header>
