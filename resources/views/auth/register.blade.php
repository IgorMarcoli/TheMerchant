@extends('layouts.app')

@section('title', 'Criar Conta')

@section('content')
<x-auth-card title="Criar Nova Conta" eyebrow="SEU PRÓXIMO NÍVEL COMEÇA AQUI" description="Faça parte da comunidade e encontre seu próximo upgrade.">

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="register-name" class="block text-xs font-semibold text-slate-300 mb-1">Nome Completo</label>
                <input id="register-name" type="text" name="name" value="{{ old('name') }}" required autofocus class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label for="register-email" class="block text-xs font-semibold text-slate-300 mb-1">E-mail</label>
                <input id="register-email" type="email" name="email" value="{{ old('email') }}" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
            </div>

            <p class="text-xs text-slate-400">Uma conta para comprar e vender. Depois do cadastro, solicite a aprovação do seu perfil de vendedor.</p>

            <div>
                <label for="register-password" class="block text-xs font-semibold text-slate-300 mb-1">Senha (Mínimo 8 caracteres)</label>
                <input id="register-password" type="password" name="password" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label for="register-password_confirmation" class="block text-xs font-semibold text-slate-300 mb-1">Confirmar Senha</label>
                <input id="register-password_confirmation" type="password" name="password_confirmation" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
            </div>

            <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold rounded-xl text-xs shadow-lg shadow-brand-600/10 transition">
                Finalizar Cadastro
            </button>
        </form>

        <p class="text-center text-xs text-slate-400 mt-6">
            Já possui uma conta? <a href="{{ route('login') }}" class="text-brand-400 font-bold hover:underline">Fazer login</a>
        </p>
</x-auth-card>
@endsection
