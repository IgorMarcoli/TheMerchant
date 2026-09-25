@if ($paginator->hasPages())
    <nav aria-label="Paginação do catálogo" class="flex flex-wrap items-center justify-center gap-2 text-sm">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="p-3 text-slate-500">Anterior</span>
        @else
            <a rel="prev" href="{{ $paginator->previousPageUrl() }}" class="p-3 rounded-lg bg-slate-900 border border-slate-700 hover:border-brand-500">Anterior</a>
        @endif
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="p-3 text-slate-400">{{ $element }}</span>
            @else
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" aria-label="Página {{ $page }}" class="min-w-11 p-3 text-center rounded-lg bg-brand-600 text-slate-950">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="Ir para página {{ $page }}" class="min-w-11 p-3 text-center rounded-lg bg-slate-900 border border-slate-700 hover:border-brand-500">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())
            <a rel="next" href="{{ $paginator->nextPageUrl() }}" class="p-3 rounded-lg bg-slate-900 border border-slate-700 hover:border-brand-500">Próxima</a>
        @else
            <span aria-disabled="true" class="p-3 text-slate-500">Próxima</span>
        @endif
    </nav>
@endif
