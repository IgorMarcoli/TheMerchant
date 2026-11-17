@extends('errors.layout', [
    'code' => 404,
    'icon' => 'search',
    'heading' => 'Esta página não foi encontrada.',
    'message' => 'O endereço pode ter mudado ou o anúncio não está mais disponível. Tente procurar pelo catálogo.',
])

@section('title', 'Página não encontrada')
