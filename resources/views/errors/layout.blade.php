@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto text-center rounded-xl bg-slate-900 border border-slate-800 px-6 py-12 sm:px-12">
    <span class="tm-empty-symbol"><x-icon :name="$icon ?? 'search'" /></span>
    <x-page-heading class="mt-6" :eyebrow="'ERRO ' . $code" :title="$heading" :description="$message" />
    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a class="tm-button" href="{{ route('listings.index') }}">Explorar o catálogo <x-icon /></a>
        <a class="inline-flex items-center px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-sm" href="{{ route('home') }}">Voltar ao início</a>
    </div>
</div>
@endsection
