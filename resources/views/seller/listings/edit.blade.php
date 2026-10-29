@extends('layouts.app')

@section('title', 'Editar Anúncio')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">
        <a href="{{ route('seller.anuncios.index') }}" class="text-sm text-brand-300">&larr; Meus anúncios</a>
        <x-page-heading title="Editar anúncio" eyebrow="SEU INVENTÁRIO" description="Atualize a oferta, escolha a capa e gerencie as fotos." />
        @include('seller.listings.form', ['editing' => true])
    </div>
@endsection
