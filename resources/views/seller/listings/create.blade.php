@extends('layouts.app')

@section('title', 'Criar Novo Anúncio')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <a href="{{ route('seller.anuncios.index') }}" class="text-sm text-brand-300">&larr; Meus anúncios</a>
        <x-page-heading title="Publicar novo anúncio" eyebrow="PREPARE SEU PRÓXIMO DROP" description="Cadastre um cosmético único ou sessões de coaching com até seis fotos." />
        @include('seller.listings.form', ['editing' => false])
    </div>
@endsection
