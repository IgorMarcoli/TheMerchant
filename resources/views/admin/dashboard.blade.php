@extends('layouts.app')

@section('title', 'Painel Administrativo')

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Cabeçalho e Navegação do Painel -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-6">
        <div>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>🛡️</span> Painel de Controle Administrativo
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Visão consolidada de indicadores de transação, catálogo, moderação e controle de contas.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.usuarios.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition flex items-center gap-1.5 border border-slate-700">
                <span>👥</span> Gerenciar Contas
            </a>
            <a href="{{ route('admin.categorias.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition flex items-center gap-1.5 border border-slate-700">
                <span>🏷️</span> Categorias & Jogos
            </a>
            <a href="{{ route('admin.reports.index') }}" class="px-3.5 py-2 rounded-xl bg-rose-950/60 hover:bg-rose-900/60 text-rose-300 text-xs font-semibold transition flex items-center gap-1.5 border border-rose-800/50">
                <span>🚩</span> Moderação ({{ $metrics['open_reports'] }})
            </a>
        </div>
    </div>

    <!-- Cards de KPIs (RF17, RF18, RNF03) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- KPI 1: Volume Financeiro Transacionado (GMV) -->
        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-400 mb-2">
                    <span class="text-xs uppercase font-bold tracking-wider">Volume Transacionado</span>
                    <span class="text-base">💳</span>
                </div>
                <div class="text-2xl font-black text-brand-400">
                    R$ {{ number_format($metrics['transacted_volume'], 2, ',', '.') }}
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-800/80 text-[11px] text-slate-400 leading-tight">
                Gross Merchandise Volume (GMV) bruto de pedidos pagos/concluídos. Não representa receita ou comissão da plataforma.
            </div>
        </div>

        <!-- KPI 2: Volume de Pedidos -->
        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-400 mb-2">
                    <span class="text-xs uppercase font-bold tracking-wider">Volume de Pedidos</span>
                    <span class="text-base">📦</span>
                </div>
                <div class="text-2xl font-black text-white">
                    {{ $metrics['total_orders'] }}
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center gap-2 text-[11px]">
                <span class="px-2 py-0.5 rounded-md font-bold bg-emerald-950 text-emerald-400 border border-emerald-800/60">
                    {{ $metrics['paid_orders'] }} pagos
                </span>
                <span class="px-2 py-0.5 rounded-md font-bold bg-amber-950 text-amber-400 border border-amber-800/60">
                    {{ $metrics['pending_orders'] }} pend.
                </span>
                <span class="px-2 py-0.5 rounded-md font-bold bg-slate-800 text-slate-400">
                    {{ $metrics['cancelled_orders'] }} canc.
                </span>
            </div>
        </div>

        <!-- KPI 3: Usuários e Contas -->
        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-400 mb-2">
                    <span class="text-xs uppercase font-bold tracking-wider">Usuários Cadastrados</span>
                    <span class="text-base">👤</span>
                </div>
                <div class="text-2xl font-black text-white">
                    {{ $metrics['total_users'] }}
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center gap-2 text-[11px]">
                <span class="text-emerald-400 font-bold">
                    {{ $metrics['active_users'] }} ativos
                </span>
                <span>•</span>
                <span class="text-amber-400 font-bold">
                    {{ $metrics['approved_sellers'] }} vend.
                </span>
                <span>•</span>
                <span class="text-rose-400 font-bold">
                    {{ $metrics['suspended_users'] }} susp.
                </span>
            </div>
        </div>

        <!-- KPI 4: Catálogo e Anúncios Ativos -->
        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-400 mb-2">
                    <span class="text-xs uppercase font-bold tracking-wider">Anúncios no Catálogo</span>
                    <span class="text-base">💎</span>
                </div>
                <div class="text-2xl font-black text-brand-400">
                    {{ $metrics['active_listings'] }}
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-800/80 text-[11px] text-slate-400">
                {{ $metrics['total_listings'] }} anúncios totais (inclui pausados, vendidos e bloqueados).
            </div>
        </div>
    </div>

    <!-- Seções de Detalhamento e Listagens Recentes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Pedidos Recentes -->
        <div class="rounded-3xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
            <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
                <h2 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>🛒</span> Últimos Pedidos Registrados
                </h2>
                <span class="text-xs text-slate-500 font-semibold">Exibindo 5 mais recentes</span>
            </div>

            <div class="divide-y divide-slate-800/60">
                @forelse($recentOrders as $order)
                    <div class="py-3 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-mono font-bold text-white">{{ $order->order_number }}</span>
                            <span class="text-slate-400 ml-2">{{ $order->buyer?->name ?? 'Comprador' }}</span>
                            <div class="text-[10px] text-slate-500 mt-0.5">
                                {{ $order->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-extrabold text-brand-400 block">
                                R$ {{ number_format($order->total_amount, 2, ',', '.') }}
                            </span>
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded
                                {{ in_array($order->status, ['pago', 'em_andamento', 'concluido']) ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/40' :
                                   ($order->status === 'pendente' ? 'bg-amber-950 text-amber-400 border border-amber-800/40' : 'bg-slate-800 text-slate-400') }}">
                                {{ $order->status }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-slate-500">
                        Nenhum pedido registrado no momento.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Denúncias Pendentes -->
        <div class="rounded-3xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
            <div class="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
                <h2 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>🚩</span> Denúncias Pendentes de Moderação
                </h2>
                <a href="{{ route('admin.reports.index') }}" class="text-xs text-brand-400 hover:text-brand-300 font-semibold">
                    Ver todas &rarr;
                </a>
            </div>

            <div class="divide-y divide-slate-800/60">
                @forelse($recentReports as $rep)
                    <div class="py-3 flex items-center justify-between text-xs">
                        <div class="max-w-[75%]">
                            <span class="font-bold text-rose-300">{{ $rep->reason }}</span>
                            <p class="text-slate-400 text-[11px] truncate mt-0.5">{{ $rep->details }}</p>
                            <span class="text-[10px] text-slate-500">
                                Anúncio: {{ $rep->listing?->title ?? 'Anúncio removido' }}
                            </span>
                        </div>
                        <a href="{{ route('admin.reports.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition">
                            Moderar
                        </a>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-slate-500">
                        Nenhuma denúncia pendente de análise no momento.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
