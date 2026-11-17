@extends('layouts.app')
@section('title', 'Contas e vendedores')
@section('content')
<div class="space-y-6">
    <x-page-heading title="Contas e vendedores" eyebrow="Administração" description="Gerencie contas, solicitações de vendedores e permissões de acesso." />
    <form method="GET" class="flex flex-wrap gap-3">
        <input aria-label="Buscar por nome ou e-mail" name="busca" value="{{ request('busca') }}" placeholder="Nome ou e-mail" class="bg-slate-900 rounded-lg p-2">
        <select aria-label="Filtrar contas" name="access" class="bg-slate-900 rounded-lg p-2">
            @foreach (['' => 'Todas as contas', 'pending' => 'Solicitações pendentes', 'approved' => 'Vendedores aprovados', 'suspended' => 'Vendas suspensas', 'admin' => 'Administradores'] as $value => $label)
                <option value="{{ $value }}" @selected(request('access', '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="tm-button">Filtrar</button>
    </form>
    @forelse ($users as $account)
        <form action="{{ route('admin.usuarios.update', $account) }}" method="POST" class="space-y-3 rounded-xl bg-slate-900 p-5">
            @csrf
            @method('PUT')
            @php
                // Só o formulário da conta que foi enviada mostra os erros de validação.
                $failed = (string) old('account_id') === (string) $account->id;
                $errorId = fn (string $field) => "account-{$account->id}-{$field}-error";
                $invalid = fn (string $field) => $failed && $errors->has($field);
            @endphp
            <input type="hidden" name="account_id" value="{{ $account->id }}">
            <h2 class="font-bold">{{ $account->name }} ({{ $account->email }})</h2>
            @if ($account->sellerProfile)
                <p>{{ $account->sellerProfile->bio }}</p>
                <label class="block">Vendas
                    <select name="seller_status" class="bg-slate-950 rounded-lg p-2" @if($invalid('seller_status')) aria-invalid="true" aria-describedby="{{ $errorId('seller_status') }}" @endif>
                        @foreach (['pending' => 'Pendente', 'approved' => 'Aprovadas', 'suspended' => 'Suspensas'] as $value => $label)
                            <option value="{{ $value }}" @selected($account->sellerProfile->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                @if ($failed)<x-field-error name="seller_status" :id="$errorId('seller_status')" />@endif
            @else
                <p>Sem solicitação de vendedor.</p>
            @endif
            <label class="block">Conta
                <select name="status" class="bg-slate-950 rounded-lg p-2" @if($invalid('status')) aria-invalid="true" aria-describedby="{{ $errorId('status') }}" @endif>
                    <option value="active" @selected($account->status === 'active')>Ativa</option>
                    <option value="suspended" @selected($account->status === 'suspended')>Suspensa</option>
                </select>
            </label>
            @if ($failed)<x-field-error name="status" :id="$errorId('status')" />@endif
            <input type="hidden" name="is_admin" value="0">
            <label class="block"><input type="checkbox" name="is_admin" value="1" @checked($account->is_admin) @if($invalid('is_admin')) aria-invalid="true" aria-describedby="{{ $errorId('is_admin') }}" @endif> Acesso administrativo</label>
            @if ($failed)<x-field-error name="is_admin" :id="$errorId('is_admin')" />@endif
            <button class="tm-button">Salvar permissões</button>
        </form>
    @empty
        <p>Nenhuma conta encontrada.</p>
    @endforelse
    {{ $users->links() }}
</div>
@endsection
