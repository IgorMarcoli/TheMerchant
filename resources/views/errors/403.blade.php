@extends('errors.layout', [
    'code' => 403,
    'icon' => 'shield',
    'heading' => 'Você não tem acesso a esta página.',
    'message' => 'Esta área é restrita a outra conta ou perfil. Se acredita que deveria ter acesso, entre com a conta correta.',
])

@section('title', 'Acesso negado')
