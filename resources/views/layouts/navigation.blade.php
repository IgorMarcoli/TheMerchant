<nav class="border-b border-slate-800 bg-slate-900/80 backdrop-blur-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <!-- Logo & Brand -->
            <div class="flex items-center gap-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-black tracking-tight text-white group">
                    <span class="p-2 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition">🎮</span>
                    <span>The<span class="text-indigo-400">Merchant</span></span>
                </a>
                <div class="hidden md:flex items-center gap-4 text-sm font-medium text-slate-300">
                    <a href="{{ route('listings.index') }}" class="hover:text-white transition px-3 py-2 rounded-lg hover:bg-slate-800">Explorar Catálogo</a>
                </div>
            </div>

            <!-- Actions & Auth -->
            <div class="flex items-center gap-4">
                @auth
                    <!-- Carrinho -->
                    <a href="{{ route('cart.index') }}" class="relative p-2 rounded-lg hover:bg-slate-800 text-slate-300 hover:text-white transition" title="Carrinho">
                        🛒
                    </a>

                    <!-- Meus Pedidos -->
                    <a href="{{ route('orders.index') }}" class="text-sm font-medium text-slate-300 hover:text-white px-3 py-2 rounded-lg hover:bg-slate-800 transition">
                        Meus Pedidos
                    </a>

                    <!-- Painel do Vendedor -->
                    @if(auth()->user()->isSeller())
                        <a href="{{ route('seller.anuncios.index') }}" class="text-sm font-medium text-amber-400 hover:text-amber-300 px-3 py-2 rounded-lg hover:bg-amber-950/40 border border-amber-500/30 transition">
                            💼 Painel Vendedor
                        </a>
                    @endif

                    <!-- Painel do Admin -->
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-rose-400 hover:text-rose-300 px-3 py-2 rounded-lg hover:bg-rose-950/40 border border-rose-500/30 transition">
                            🛡️ Admin
                        </a>
                    @endif

                    <!-- User Menu / Logout -->
                    <div class="flex items-center gap-2 pl-4 border-l border-slate-800">
                        <span class="text-xs text-slate-400 font-medium hidden sm:inline">{{ auth()->user()->name }}</span>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white px-3 py-1.5 rounded-lg transition font-medium">
                                Sair
                            </button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-300 hover:text-white transition px-3 py-2">
                        Entrar
                    </a>
                    <a href="{{ route('register') }}" class="text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-xl shadow-lg shadow-indigo-600/30 transition">
                        Criar Conta
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>
