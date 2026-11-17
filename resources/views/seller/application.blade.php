@extends('layouts.app')
@section('title', 'Quero vender')
@section('content')
<div class="max-w-2xl mx-auto space-y-6 rounded-2xl bg-slate-900 p-6">
    <x-page-heading title="Quero vender" eyebrow="Vender no TheMerchant" description="Conte um pouco sobre o que você quer vender. A equipe revisa e libera seu perfil de vendedor." />
    @if ($profile)
        <p>Situação: <strong>{{ ['pending' => 'Aguardando aprovação', 'approved' => 'Aprovado', 'suspended' => 'Vendas suspensas'][$profile->status] }}</strong></p>
        @if ($profile->status === 'approved')
            <a class="tm-button" href="{{ route('seller.anuncios.create') }}">Criar anúncio</a>
        @elseif ($profile->status === 'suspended')
            <p>Você pode continuar comprando e concluir entregas pendentes. A equipe precisa reativar seu perfil para novas vendas.</p>
        @else
            <p>Sua solicitação está em análise. Enquanto isso, você pode continuar comprando.</p>
        @endif
        <a class="block text-brand-400" href="{{ route('seller.sales.index') }}">Consultar minhas vendas</a>
    @else
        <form action="{{ route('seller.application.store') }}" method="POST" class="space-y-4">
            @csrf
            <label for="bio" class="block">Apresentação do vendedor</label>
            <textarea id="bio" name="bio" required @error('bio') aria-invalid="true" aria-describedby="bio-error" @enderror minlength="10" maxlength="500" rows="5" class="w-full rounded-lg bg-slate-950 p-3" placeholder="Conte quais itens ou serviços pretende vender e sua experiência.">{{ old('bio') }}</textarea>
            <x-field-error name="bio" id="bio-error" />
            <button class="tm-button" type="submit">Solicitar aprovação</button>
        </form>
    @endif
</div>
@endsection
