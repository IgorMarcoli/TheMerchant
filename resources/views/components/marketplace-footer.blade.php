<footer class="market-footer" aria-label="Rodapé do marketplace">
    <div class="market-footer-inner">
        <div class="market-footer-banner">
            <div>
                <span class="market-footer-eyebrow">DE PLAYER PARA PLAYER</span>
                <h2>Seu próximo upgrade está aqui.</h2>
                <p>Encontre seu estilo, evolua no jogo e faça parte da comunidade.</p>
            </div>
            <a class="market-footer-cta" href="{{ route('listings.index') }}">Explorar marketplace <x-icon /></a>
        </div>

        <div class="market-footer-grid">
            <div class="market-footer-brand">
                <a href="{{ route('home') }}" class="market-footer-logo" aria-label="TheMerchant — início">
                    <span class="market-footer-mark"><x-icon name="store" /></span>
                    <span>the<span class="market-footer-accent">merchant.</span></span>
                </a>
                <p>Compre e venda skins, personalize seu inventário e encontre coaching para evoluir nas partidas.</p>
                <span class="market-footer-signature"><x-icon name="game" /> De gamers, para gamers</span>
            </div>

            <nav aria-label="Explorar o marketplace">
                <h3>Explore</h3>
                <ul>
                    <li><a href="{{ route('listings.index') }}">Todos os anúncios</a></li>
                    <li><a href="{{ route('listings.index', ['tipo' => 'cosmetic']) }}">Skins e cosméticos</a></li>
                    <li><a href="{{ route('listings.index', ['tipo' => 'service']) }}">Coaching e serviços</a></li>
                    <li><a href="{{ route('home') }}#jogos">Encontre seu jogo</a></li>
                </ul>
            </nav>

            <nav aria-label="Área do comprador">
                <h3>Para comprar</h3>
                <ul>
                    <li><a href="{{ route('home') }}#como-funciona">Como funciona</a></li>
                    <li><a href="{{ route('cart.index') }}">Meu carrinho</a></li>
                    <li><a href="{{ route('orders.index') }}">Acompanhar pedidos</a></li>
                    <li><a href="{{ route('profile.edit') }}">Minha conta</a></li>
                </ul>
            </nav>

            <nav aria-label="Área do vendedor">
                <h3>Para vender</h3>
                <ul>
                    <li><a href="{{ route('seller.application') }}">Criar um anúncio</a></li>
                    <li><a href="{{ route('seller.anuncios.index') }}">Meus anúncios</a></li>
                    <li><a href="{{ route('seller.sales.index') }}">Minhas vendas</a></li>
                    @guest
                        <li><a href="{{ route('register') }}">Começar no TheMerchant</a></li>
                    @endguest
                </ul>
            </nav>
        </div>

        <div class="market-footer-bottom">
            <div>
                <p>&copy; {{ date('Y') }} TheMerchant. Todos os direitos reservados.</p>
                <small>As marcas e os jogos mencionados pertencem aos seus respectivos titulares.</small>
            </div>
            <span class="market-footer-locale">Português (Brasil) <span aria-hidden="true">/</span> R$ BRL</span>
        </div>
    </div>
</footer>
