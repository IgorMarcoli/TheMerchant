@extends('errors.layout', [
    'code' => 419,
    'icon' => 'shield',
    'heading' => 'Sua sessão expirou.',
    'message' => 'Por segurança, o formulário ficou inválido depois de muito tempo aberto. Volte à página, recarregue e envie novamente.',
])

@section('title', 'Sessão expirada')
