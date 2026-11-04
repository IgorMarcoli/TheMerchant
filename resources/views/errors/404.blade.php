@extends('errors.layout', [
    'code' => 404,
    'icon' => 'search',
    'heading' => 'Esta página não foi encontrada.',
    'message' => 'O endereço pode ter mudado ou o anúncio não está mais disponível. Explore o catálogo para encontrar seu próximo upgrade.',
])

@section('title', 'Página não encontrada')
