@props(['title'])

<div class="max-w-md mx-auto py-8">
    <div class="rounded-3xl bg-slate-900 border border-slate-800 p-8 shadow-2xl">
        <h1 class="text-2xl font-black text-white mb-4">{{ $title }}</h1>
        @if ($errors->any())
            <div role="alert" class="mb-4 p-3 rounded-xl bg-rose-950/80 border border-rose-500/30 text-rose-300 text-sm">
                {{ $errors->first() }}
            </div>
        @endif
        {{ $slot }}
    </div>
</div>
