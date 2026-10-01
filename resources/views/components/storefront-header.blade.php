<header class="tm-header">
    <div class="tm-container tm-topbar">
        <a href="{{ route('home') }}" class="tm-logo" aria-label="TheMerchant — início"><span class="tm-logomark"><x-icon name="store" /></span><span>the<span class="tm-accent">merchant</span>.</span></a>
        <form action="{{ route('listings.index') }}" method="GET" class="tm-search" role="search">
            <label for="home-search" class="sr-only">Buscar itens ou serviços</label><x-icon name="search" />
            <input id="home-search" name="busca" type="search" placeholder="O que você procura para o seu próximo GG?" maxlength="150" value="{{ is_string(request('busca')) ? request('busca') : '' }}">
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
            <a href="{{ route('listings.index') }}" @if(request()->routeIs('listings.index') && !request('tipo')) aria-current="page" @endif><x-icon name="grid" />Explorar catálogo</a>
            <a href="{{ route('listings.index', ['tipo' => 'cosmetic']) }}" @if(request()->routeIs('listings.index') && request('tipo') === 'cosmetic') aria-current="page" @endif>Skins e cosméticos</a>
            <a href="{{ route('listings.index', ['tipo' => 'service']) }}" @if(request()->routeIs('listings.index') && request('tipo') === 'service') aria-current="page" @endif>Coaching <span class="tm-mini-label">LEVEL UP</span></a>
            <a href="{{ route('home') }}#como-funciona">Como funciona</a>
        </div>
        <a class="tm-sell-link" href="{{ route('seller.application') }}"><x-icon name="store" />Quero vender <x-icon /></a>
        <a class="tm-mobile-cart" href="{{ route('cart.index') }}"><x-icon name="cart" />Meu carrinho</a>
    </div></nav>
    @auth
        @php
            $unreadChatCount = app(\App\Services\ChatService::class)->getUnreadCountForUser(auth()->user());
        @endphp
        <nav class="tm-workspace-nav" aria-label="Minha área">
            <div class="tm-container tm-workspace-links">
                <a href="{{ route('orders.index') }}" @if(request()->routeIs('orders.*')) aria-current="page" @endif>Meus pedidos</a>
                <a href="{{ route('chat.index') }}" @if(request()->routeIs('chat.*')) aria-current="page" @endif>
                    Mensagens
                    @if ($unreadChatCount > 0)
                        <span class="tm-mini-label" aria-label="{{ $unreadChatCount }} mensagens não lidas">{{ $unreadChatCount > 99 ? '99+' : $unreadChatCount }}</span>
                    @endif
                </a>
                <a href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif>Meu perfil</a>
                @if(auth()->user()->isSeller())
                    <a href="{{ route('seller.anuncios.index') }}" @if(request()->routeIs('seller.anuncios.*')) aria-current="page" @endif>Meus anúncios</a>
                    <a href="{{ route('seller.sales.index') }}" @if(request()->routeIs('seller.sales.*')) aria-current="page" @endif>Minhas vendas</a>
                @endif
                @can('viewAny', \App\Models\User::class)
                    <a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>Administração</a>
                    <a href="{{ route('admin.usuarios.index') }}" @if(request()->routeIs('admin.usuarios.*')) aria-current="page" @endif>Contas</a>
                    <a href="{{ route('admin.categorias.index') }}" @if(request()->routeIs('admin.categorias.*')) aria-current="page" @endif>Categorias e jogos</a>
                    <a href="{{ route('admin.reports.index') }}" @if(request()->routeIs('admin.reports.*')) aria-current="page" @endif>Denúncias</a>
                @endcan
            </div>
        </nav>
    @endauth
</header>
