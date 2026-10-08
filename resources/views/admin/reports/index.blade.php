@extends('layouts.app')

@section('title', 'Moderação de Denúncias')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <x-page-heading title="Fila de Moderação de Denúncias" eyebrow="UMA COMUNIDADE BEM CUIDADA" description="Analise denúncias e suspenda anúncios irregulares." />
    </div>

    <form method="GET" action="{{ route('admin.reports.index') }}" class="mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-slate-800 bg-slate-900 p-4">
        <div>
            <label for="status" class="mb-1 block text-xs text-slate-400">Filtrar por situação</label>
            <select id="status" name="status" class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white">
                <option value="">Todas as denúncias</option>
                @foreach(['aberta' => 'Abertas', 'em_analise' => 'Em análise', 'procedente' => 'Procedentes', 'improcedente' => 'Improcedentes'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">Filtrar</button>
    </form>

    <div class="space-y-4">
        @forelse($reports as $report)
            <div class="rounded-2xl bg-slate-900 border border-slate-800 p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <span class="text-xs font-bold text-rose-400 uppercase tracking-wider block">{{ $report->reason }}</span>
                        <h3 class="text-sm font-semibold text-white mt-1">Anúncio: {{ $report->listing?->title ?? 'Anúncio Excluído' }}</h3>
                        <span class="text-xs text-slate-500">Denunciante: {{ $report->reporter->name }} &bull; {{ $report->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider
                        {{ $report->status === 'aberta' ? 'bg-rose-950 text-rose-400 border border-rose-800' : 'bg-slate-800 text-slate-400' }}">
                        {{ str_replace('_', ' ', $report->status) }}
                    </span>
                </div>

                <p class="text-xs text-slate-300 bg-slate-950 p-3 rounded-xl border border-slate-800 mb-4">
                    "{{ $report->details }}"
                </p>

                @if(in_array($report->status, ['aberta', 'em_analise'], true))
                    <form action="{{ route('admin.reports.moderate', $report) }}" method="POST" class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-end gap-3">
                        @csrf
                        @method('PATCH')
                        <div class="flex-1 w-full">
                            <label class="block text-[11px] text-slate-400 mb-1">Parecer da Moderação</label>
                            <input type="text" name="resolution_notes" required minlength="10" maxlength="1000" value="{{ old('resolution_notes') }}" placeholder="Justificativa da decisão..." class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white">
                        </div>
                        <div>
                            <select name="status" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white">
                                <option value="em_analise">Em análise</option>
                                <option value="procedente">Procedente</option>
                                <option value="improcedente">Improcedente</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="block_listing" value="1" id="blk-{{ $report->id }}" class="rounded bg-slate-800">
                            <label for="blk-{{ $report->id }}" class="text-[11px] text-rose-300">Bloquear se procedente</label>
                        </div>
                        <button type="submit" class="px-4 py-1.5 bg-brand-600 hover:bg-brand-500 text-slate-950 rounded-xl text-xs font-semibold">
                            Julgar
                        </button>
                    </form>
                @else
                    <div class="text-[11px] text-slate-400 pt-2 border-t border-slate-800">
                        Parecer: {{ $report->resolution_notes }}
                        @if($report->moderator)
                            <span class="block mt-1 text-slate-500">Moderada por {{ $report->moderator->name }}{{ $report->updated_at ? ' em '.$report->updated_at->format('d/m/Y H:i') : '' }}</span>
                        @endif
                    </div>
                @endif

                @if($report->audits->isNotEmpty())
                    <details class="mt-4 border-t border-slate-800 pt-3">
                        <summary class="cursor-pointer text-[11px] font-semibold text-slate-400">Histórico de moderação ({{ $report->audits->count() }})</summary>
                        <ol class="mt-3 space-y-2">
                            @foreach($report->audits as $audit)
                                <li class="rounded-lg bg-slate-950 px-3 py-2 text-[11px] text-slate-400">
                                    <span class="font-semibold text-slate-200">{{ $audit->moderator?->name ?? 'Moderador removido' }}</span>
                                    <span>alterou {{ str_replace('_', ' ', $audit->previous_report_status ?? 'nova') }} para {{ str_replace('_', ' ', $audit->new_report_status) }}</span>
                                    @if($audit->new_listing_status === 'bloqueado' && $audit->previous_listing_status !== 'bloqueado')
                                        <span>e bloqueou o anúncio</span>
                                    @endif
                                    <span class="block text-slate-500">{{ $audit->created_at->format('d/m/Y H:i') }} · {{ $audit->resolution_notes }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </details>
                @endif
            </div>
        @empty
            <x-empty-state title="Nenhuma denúncia registrada." description="As denúncias recebidas pela comunidade aparecerão aqui para análise." icon="shield" />
        @endforelse
    </div>

    <div class="mt-8">
        {{ $reports->links() }}
    </div>
</div>
@endsection
