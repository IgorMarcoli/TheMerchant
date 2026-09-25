@extends('layouts.app')

@section('title', 'Recuperar acesso')

@section('content')
<x-auth-card title="Esqueci minha senha">
    <p class="text-sm text-slate-400 mb-6">Informe seu e-mail para receber um link de recuperação com validade de 60 minutos.</p>
    <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm text-slate-300 mb-1">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" maxlength="150" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white">
        </div>
        <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold rounded-xl">Enviar link de recuperação</button>
    </form>
    <a href="{{ route('login') }}" class="block text-center text-sm text-brand-400 mt-6">Voltar para entrar</a>
</x-auth-card>
@endsection
