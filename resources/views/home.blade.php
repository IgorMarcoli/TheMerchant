@extends('layouts.app')

@section('title', 'Hello World - TheMerchant')

@section('content')
<div class="max-w-4xl mx-auto space-y-8 py-4">

    <!-- Hero / Hello World -->
    <div class="text-center space-y-4">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
            <span>🎓</span> Laboratório de Engenharia de Software III (2026) — FATEC PG
        </div>

        <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-white">
            Hello World! <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400">TheMerchant</span> 🎮
        </h1>

        <p class="text-sm sm:text-base text-slate-300 max-w-2xl mx-auto leading-relaxed">
            Projeto criado, versionado no GitHub e com o banco de dados configurado com sucesso. Abaixo você confere o teste em tempo real do banco de dados e os atalhos para as rotinas de controle de acesso.
        </p>
    </div>

    <!-- Painel de Teste do Banco de Dados -->
    <div class="rounded-3xl bg-slate-900 border border-slate-800 p-8 shadow-2xl relative overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-slate-800">
            <div>
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span>🗄️</span> Teste de Conexão com o Banco de Dados
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Diagnóstico em tempo real da conexão com o MySQL / phpMyAdmin.</p>
            </div>

            <div>
                @if ($dbStatus['connected'])
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 text-xs font-bold">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Conectado com Sucesso
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-rose-950/80 border border-rose-500/40 text-rose-300 text-xs font-bold">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span> Falha na Conexão
                    </span>
                @endif
            </div>
        </div>

        @if ($dbStatus['connected'])
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 my-6">
                <!-- Driver & Host -->
                <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Driver & Host</span>
                    <span class="text-sm font-bold text-white mt-1 block">
                        {{ strtoupper($dbStatus['driver']) }} ({{ $dbStatus['host'] }}:{{ $dbStatus['port'] }})
                    </span>
                </div>

                <!-- Database Name -->
                <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Banco de Dados</span>
                    <span class="text-sm font-bold text-indigo-400 mt-1 block">
                        {{ $dbStatus['database'] }}
                    </span>
                </div>

                <!-- Users Table Status -->
                <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Tabela de Usuários</span>
                    <span class="text-sm font-bold text-emerald-400 mt-1 flex items-center gap-1.5">
                        @if ($dbStatus['hasUsersTable'])
                            ✅ {{ $dbStatus['usersCount'] }} usuários no banco
                        @else
                            ⚠️ Tabela não encontrada
                        @endif
                    </span>
                </div>
            </div>

            <!-- Tabelas Detectadas -->
            <div class="p-4 rounded-2xl bg-slate-950/40 border border-slate-800/80 mb-6">
                <span class="text-xs font-bold text-slate-300 block mb-2">
                    📋 Tabelas Criadas no MySQL ({{ count($dbStatus['tables']) }} tabelas encontradas):
                </span>
                <div class="flex flex-wrap gap-2">
                    @foreach ($dbStatus['tables'] as $tableName)
                        <span class="text-[11px] px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 font-mono border border-slate-700/60">
                            {{ $tableName }}
                        </span>
                    @endforeach
                </div>
            </div>
        @else
            <!-- Erro de Conexão -->
            <div class="my-6 p-4 rounded-2xl bg-rose-950/50 border border-rose-500/40 text-rose-200 text-xs space-y-2">
                <p class="font-bold">Não foi possível conectar ao banco de dados MySQL:</p>
                <p class="font-mono text-[11px] bg-rose-950 p-2 rounded-lg">{{ $dbStatus['error'] }}</p>
                <p class="text-slate-300">💡 <strong>Dica:</strong> Certifique-se de que o módulo MySQL no XAMPP está iniciado e que o banco de dados <code>{{ $dbStatus['database'] }}</code> foi criado.</p>
            </div>
        @endif

        <!-- Botão de Re-teste -->
        <div class="flex justify-end">
            <a href="{{ route('home') }}" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-xl border border-slate-700 transition flex items-center gap-2">
                <span>🔄</span> Testar Conexão Novamente
            </a>
        </div>
    </div>

    <!-- Rotinas de Controle de Acesso Solicitadas pelo Professor -->
    <div class="rounded-3xl bg-slate-900 border border-slate-800 p-8 shadow-xl">
        <h2 class="text-xl font-bold text-white flex items-center gap-2 mb-2">
            <span>🔐</span> Rotinas de Controle de Acesso (Solicitação do Professor)
        </h2>
        <p class="text-xs text-slate-400 mb-6">Clique nos módulos abaixo para testar as telas e regras de negócio implementadas:</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Login -->
            <a href="{{ route('login') }}" class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-950 transition flex flex-col items-center text-center group">
                <span class="text-3xl mb-2 group-hover:scale-110 transition">🔑</span>
                <h3 class="text-sm font-bold text-white group-hover:text-indigo-400 transition">Testar Login</h3>
                <p class="text-[11px] text-slate-400 mt-1">Autenticação por sessão segura e Bcrypt.</p>
            </a>

            <!-- Cadastro -->
            <a href="{{ route('register') }}" class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-950 transition flex flex-col items-center text-center group">
                <span class="text-3xl mb-2 group-hover:scale-110 transition">📝</span>
                <h3 class="text-sm font-bold text-white group-hover:text-indigo-400 transition">Testar Cadastro</h3>
                <p class="text-[11px] text-slate-400 mt-1">Criação de contas Comprador e Vendedor.</p>
            </a>

            <!-- Perfil & Troca de Senha -->
            <a href="{{ route('profile.edit') }}" class="p-5 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-950 transition flex flex-col items-center text-center group">
                <span class="text-3xl mb-2 group-hover:scale-110 transition">👤</span>
                <h3 class="text-sm font-bold text-white group-hover:text-indigo-400 transition">Perfil & Troca de Senha</h3>
                <p class="text-[11px] text-slate-400 mt-1">Edição de dados cadastrais e alteração de senha.</p>
            </a>
        </div>
    </div>

    <!-- Rodapé de Metadados Acadêmicos -->
    <div class="text-center text-xs text-slate-500 space-y-1">
        <p>Desenvolvido por <strong>Igor Marcoli Bastos</strong> e <strong>João Pedro Martins de Andrade</strong></p>
        <p>Repositório Oficial: <a href="https://github.com/IgorMarcoli/TheMerchant" target="_blank" class="text-indigo-400 hover:underline">github.com/IgorMarcoli/TheMerchant</a></p>
    </div>

</div>
@endsection
