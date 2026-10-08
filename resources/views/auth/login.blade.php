@extends('layouts.app')

@section('title', 'Acessar Conta')

@section('content')
<x-auth-card title="Bem-vindo de volta!" eyebrow="BOM TER VOCÊ DE VOLTA" description="Entre com seu e-mail e senha para continuar.">

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="login-email" class="block text-xs font-semibold text-slate-300 mb-1">E-mail</label>
                <input id="login-email" type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label for="login-password" class="block text-xs font-semibold text-slate-300 mb-1">Senha</label>
                <input id="login-password" type="password" name="password" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 text-slate-400">
                    <input type="checkbox" name="remember" class="rounded bg-slate-950 border-slate-700 text-brand-600">
                    Lembrar-me
                </label>
                <a href="{{ route('password.request') }}" class="text-brand-400 hover:text-brand-300">Esqueci minha senha</a>
            </div>

            <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold rounded-xl text-xs shadow-lg shadow-brand-600/10 transition">
                Entrar na Plataforma
            </button>
        </form>

        <p class="text-center text-xs text-slate-400 mt-6">
            Não possui uma conta? <a href="{{ route('register') }}" class="text-brand-400 font-bold hover:underline">Cadastre-se</a>
        </p>
</x-auth-card>
@endsection
