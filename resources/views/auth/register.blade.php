@extends('layouts.app')

@section('title', 'Criar Conta')

@section('content')
<div class="max-w-md mx-auto py-8">
    <div class="rounded-3xl bg-slate-900 border border-slate-800 p-8 shadow-2xl">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-black text-white">Criar Nova Conta</h1>
            <p class="text-xs text-slate-400 mt-1">Junte-se ao marketplace gamer seguro.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 rounded-xl bg-rose-950/80 border border-rose-500/30 text-rose-300 text-xs">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Nome Completo</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">E-mail</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
            </div>

            <p class="text-xs text-slate-400">Uma conta para comprar e vender. Depois do cadastro, solicite a aprovação do seu perfil de vendedor.</p>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Senha (Mínimo 8 caracteres)</label>
                <input type="password" name="password" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Confirmar Senha</label>
                <input type="password" name="password_confirmation" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-brand-500">
            </div>

            <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold rounded-xl text-xs shadow-lg shadow-brand-600/10 transition">
                Finalizar Cadastro
            </button>
        </form>

        <p class="text-center text-xs text-slate-400 mt-6">
            Já possui uma conta? <a href="{{ route('login') }}" class="text-brand-400 font-bold hover:underline">Fazer login</a>
        </p>
    </div>
</div>
@endsection
