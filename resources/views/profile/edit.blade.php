@extends('layouts.app')

@section('title', 'Meu Perfil')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">
    <a href="{{ route('seller.application') }}" class="text-brand-400 font-semibold">Gerenciar meu perfil de vendedor</a>
    <!-- Header da Página -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-800/80 pb-6">
        <div>
            <h1 class="text-3xl font-black text-white tracking-tight flex items-center gap-3">
                <span>👤</span> Meu Perfil
            </h1>
            <p class="text-sm text-slate-400 mt-1">Gerencie suas informações cadastrais, preferências de conta e segurança.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs uppercase tracking-wider font-bold px-3 py-1.5 rounded-xl border {{ $user->isAdmin() ? 'bg-rose-950/60 border-rose-500/40 text-rose-300' : ($user->isSeller() ? 'bg-amber-950/60 border-amber-500/40 text-amber-300' : 'bg-brand-950/60 border-brand-500/40 text-brand-300') }}">
                {{ $user->isAdmin() ? '🛡️ Administrador' : ($user->isSeller() ? '💼 Comprador e vendedor' : '🛒 Comprador') }}
            </span>
            <span class="text-xs px-3 py-1.5 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 font-semibold">
                ● Conta Ativa
            </span>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-rose-950/80 border border-rose-500/40 text-rose-200 text-sm">
            <p class="font-bold mb-1">Por favor, corrija os erros abaixo:</p>
            <ul class="list-disc list-inside space-y-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Coluna Esquerda: Resumo do Usuário e Reputação -->
        <div class="space-y-6">
            <div class="rounded-3xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
                <div class="text-center pb-6 border-b border-slate-800">
                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center text-3xl font-black text-slate-950 shadow-lg shadow-brand-500/10 mb-3">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <h2 class="text-lg font-bold text-white">{{ $user->name }}</h2>
                    <p class="text-xs text-slate-400">{{ $user->email }}</p>
                    <span class="inline-block mt-2 text-[11px] text-slate-400 bg-slate-800/80 px-2.5 py-1 rounded-lg">
                        Membro desde {{ $user->created_at ? $user->created_at->format('d/m/Y') : date('d/m/Y') }}
                    </span>
                </div>

                @if ($user->sellerProfile)
                    <div class="pt-6 space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Desempenho Comercial</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-2xl bg-slate-950/70 border border-slate-800 text-center">
                                <span class="text-xs text-slate-400 block">Reputação</span>
                                <span class="text-base font-extrabold text-amber-400 flex items-center justify-center gap-1 mt-0.5">
                                    ⭐ {{ number_format($user->sellerProfile->reputation_score, 1) }}
                                </span>
                            </div>
                            <div class="p-3 rounded-2xl bg-slate-950/70 border border-slate-800 text-center">
                                <span class="text-xs text-slate-400 block">Vendas</span>
                                <span class="text-base font-extrabold text-white mt-0.5 block">
                                    {{ $user->sellerProfile->total_sales }}
                                </span>
                            </div>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-950/40 border border-slate-800/80">
                            <span class="text-xs font-semibold text-slate-400 block mb-1">Biografia Pública:</span>
                            <p class="text-xs text-slate-300 italic">
                                "{{ $user->sellerProfile->bio ?: 'Nenhuma biografia definida ainda.' }}"
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Dicas de Segurança -->
            <div class="rounded-3xl bg-slate-900/60 border border-slate-800/80 p-6">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2 mb-3">
                    <span>🔒</span> Dicas de Acesso Seguro
                </h3>
                <ul class="text-xs text-slate-400 space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="text-brand-400">•</span>
                        <span>Utilize senhas exclusivas com pelo menos 8 dígitos, combinando números e caracteres especiais.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-brand-400">•</span>
                        <span>Nunca compartilhe suas credenciais ou tokens de autenticação com terceiros.</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Coluna Direita: Formulários de Edição de Dados e Troca de Senha -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Formulário 1: Dados Pessoais -->
            <div class="rounded-3xl bg-slate-900 border border-slate-800 p-8 shadow-xl">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-white flex items-center gap-2">
                        <span>📝</span> Dados Pessoais
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Mantenha seu nome e endereço de e-mail atualizados.</p>
                </div>

                <form action="{{ route('profile.update') }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Nome Completo</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="120"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-brand-500 transition">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Endereço de E-mail</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="150"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-brand-500 transition">
                        </div>
                    </div>

                    @if ($user->sellerProfile)
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Biografia do Vendedor (Aparece em seus anúncios)</label>
                            <textarea name="bio" rows="3" maxlength="500"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-brand-500 transition"
                                placeholder="Conte um pouco sobre seu histórico de negociações, horários de entrega ou especialidades...">{{ old('bio', optional($user->sellerProfile)->bio) }}</textarea>
                        </div>
                    @endif

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold rounded-xl text-xs shadow-lg shadow-brand-600/10 transition flex items-center gap-2">
                            <span>💾</span> Salvar Informações
                        </button>
                    </div>
                </form>
            </div>

            <!-- Formulário 2: Alteração de Senha -->
            <div class="rounded-3xl bg-slate-900 border border-slate-800 p-8 shadow-xl">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-white flex items-center gap-2">
                        <span>🔑</span> Trocar Senha de Acesso
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Para sua segurança, informe sua senha atual antes de definir uma nova.</p>
                </div>

                <form action="{{ route('profile.password') }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Senha Atual</label>
                        <input type="password" name="current_password" required
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-brand-500 transition"
                            placeholder="Digite sua senha em uso">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Nova Senha</label>
                            <input type="password" name="password" required minlength="8"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-brand-500 transition"
                                placeholder="Mínimo 8 caracteres">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Confirmar Nova Senha</label>
                            <input type="password" name="password_confirmation" required minlength="8"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-brand-500 transition"
                                placeholder="Repita a nova senha">
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs border border-slate-700 hover:border-slate-600 transition flex items-center gap-2">
                            <span>🔐</span> Atualizar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
