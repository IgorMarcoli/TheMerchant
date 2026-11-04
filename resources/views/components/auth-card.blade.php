@props(['title', 'description' => null, 'eyebrow' => 'SUA CONTA, SEU UNIVERSO', 'fields' => []])

@php
    // Erros dos campos listados em $fields aparecem junto ao campo; o resumo cita só o que não tem lugar no formulário.
    $summary = collect($errors->getMessages())->except($fields)->flatten()->first();
@endphp

<div class="max-w-md mx-auto py-8">
    <div class="rounded-xl bg-slate-900 border border-slate-800 p-8 shadow-xl">
        <x-page-heading :title="$title" :eyebrow="$eyebrow" :description="$description" class="mb-6" />
        @if ($errors->any())
            <div role="alert" class="mb-4 p-3 rounded-xl bg-rose-950/80 border border-rose-500/30 text-rose-300 text-sm">
                {{ $summary ?? 'Revise os campos indicados abaixo.' }}
            </div>
        @endif
        {{ $slot }}
    </div>
</div>
