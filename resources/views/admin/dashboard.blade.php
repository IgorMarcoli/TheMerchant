@extends('layouts.app')

@section('title', 'Painel Administrativo')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white flex items-center gap-2">
        <span>🛡️</span> Painel de Controle Administrativo
    </h1>
    <p class="text-xs text-slate-400">Visão geral do sistema e métricas de moderação.</p>
</div>

<!-- Métricas (RF17, RF18) -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
        <span class="text-xs text-slate-400 uppercase tracking-wider block">Usuários Cadastrados</span>
        <span class="text-2xl font-black text-white mt-1 block">{{ $metrics['total_users'] }}</span>
    </div>
    <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
        <span class="text-xs text-slate-400 uppercase tracking-wider block">Anúncios Ativos</span>
        <span class="text-2xl font-black text-brand-400 mt-1 block">{{ $metrics['active_listings'] }}</span>
    </div>
    <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
        <span class="text-xs text-slate-400 uppercase tracking-wider block">Total de Pedidos</span>
        <span class="text-2xl font-black text-white mt-1 block">{{ $metrics['total_orders'] }}</span>
    </div>
    <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
        <span class="text-xs text-slate-400 uppercase tracking-wider block">Denúncias Abertas</span>
        <span class="text-2xl font-black text-rose-400 mt-1 block">{{ $metrics['open_reports'] }}</span>
    </div>
</div>

<!-- Denúncias Recentes -->
<div class="rounded-3xl bg-slate-900 border border-slate-800 p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-white">Denúncias Pendentes de Análise</h2>
        <a href="{{ route('admin.reports.index') }}" class="text-xs text-brand-400 hover:text-brand-300 font-semibold">Ver todas &rarr;</a>
    </div>

    <div class="divide-y divide-slate-800">
        @forelse($recentReports as $rep)
            <div class="py-4 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-white">{{ $rep->reason }}</span>
                    <p class="text-xs text-slate-400 mt-0.5">{{ Str::limit($rep->details, 80) }}</p>
                </div>
                <a href="{{ route('admin.reports.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-300">
                    Moderar
                </a>
            </div>
        @empty
            <div class="py-6 text-center text-xs text-slate-500">
                Nenhuma denúncia pendente de análise no momento.
            </div>
        @endforelse
    </div>
</div>
@endsection
