@extends('layouts.app')

@section('title', 'Confirmar e-mail')

@section('content')
<x-auth-card title="Confirme seu e-mail">
    @if (auth()->user()->hasVerifiedEmail())
        <p class="text-sm text-emerald-300">Seu e-mail já está confirmado.</p>
    @else
        <p class="text-sm text-slate-400 mb-6">Abra o link enviado para <strong class="text-slate-200">{{ auth()->user()->email }}</strong>. Ele vale por 60 minutos. Confira também sua pasta de spam.</p>
        <form action="{{ route('verification.send') }}" method="POST">
            @csrf
            <button type="submit" class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-slate-950 font-bold rounded-xl">Reenviar e-mail de confirmação</button>
        </form>
        <p class="text-xs text-slate-400 mt-3">Aguarde um minuto entre os reenvios.</p>
    @endif
    <a href="{{ route('profile.edit') }}" class="block text-center text-sm text-brand-400 mt-6">Voltar ao perfil ou corrigir e-mail</a>
</x-auth-card>
@endsection
