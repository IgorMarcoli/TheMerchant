@extends('layouts.app')

@section('title', 'Moderação de Denúncias')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white flex items-center gap-2">
            <span>🚩</span> Fila de Moderação de Denúncias
        </h1>
        <p class="text-xs text-slate-400">Analise denúncias e suspenda anúncios irregulares (RF15).</p>
    </div>

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
                        {{ $report->status }}
                    </span>
                </div>

                <p class="text-xs text-slate-300 bg-slate-950 p-3 rounded-xl border border-slate-800 mb-4">
                    "{{ $report->details }}"
                </p>

                @if($report->status === 'aberta')
                    <form action="{{ route('admin.reports.moderate', $report) }}" method="POST" class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-end gap-3">
                        @csrf
                        @method('PATCH')
                        <div class="flex-1 w-full">
                            <label class="block text-[11px] text-slate-400 mb-1">Parecer da Moderação</label>
                            <input type="text" name="resolution_notes" required placeholder="Justificativa da decisão..." class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white">
                        </div>
                        <div>
                            <select name="status" class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white">
                                <option value="procedente">Procedente</option>
                                <option value="improcedente">Improcedente</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="block_listing" value="1" id="blk-{{ $report->id }}" class="rounded bg-slate-800">
                            <label for="blk-{{ $report->id }}" class="text-[11px] text-rose-300">Bloquear Anúncio</label>
                        </div>
                        <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold">
                            Julgar
                        </button>
                    </form>
                @else
                    <div class="text-[11px] text-slate-400 pt-2 border-t border-slate-800">
                        Parecer: {{ $report->resolution_notes }}
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-3xl bg-slate-900 border border-slate-800 p-12 text-center text-slate-500 text-sm">
                Nenhuma denúncia registrada.
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $reports->links() }}
    </div>
</div>
@endsection
