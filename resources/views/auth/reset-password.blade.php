@extends('layouts.app')

@section('title', 'Redefinir senha')

@section('content')
<x-auth-card title="Redefinir senha">
    <p class="text-sm text-slate-400 mb-6">Escolha uma nova senha com pelo menos 8 caracteres.</p>
    <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="block text-sm text-slate-300 mb-1">E-mail</label>
            <input id="email" type="email" name="email" value="{{ $email }}" required autocomplete="email" maxlength="150" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white">
        </div>
        <div>
            <label for="password" class="block text-sm text-slate-300 mb-1">Nova senha</label>
            <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white">
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm text-slate-300 mb-1">Confirme a nova senha</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white">
        </div>
        <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold rounded-xl">Salvar nova senha</button>
    </form>
    <a href="{{ route('password.request') }}" class="block text-center text-sm text-brand-400 mt-6">Solicitar outro link</a>
</x-auth-card>
@endsection
