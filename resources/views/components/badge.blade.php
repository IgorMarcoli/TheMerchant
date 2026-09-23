@props(['type' => 'default'])

@php
$classes = match($type) {
    'success' => 'bg-emerald-950/80 text-emerald-400 border-emerald-500/30',
    'warning' => 'bg-amber-950/80 text-amber-400 border-amber-500/30',
    'danger'  => 'bg-rose-950/80 text-rose-400 border-rose-500/30',
    'brand'   => 'bg-brand-950/80 text-brand-400 border-brand-500/30',
    default   => 'bg-slate-800 text-slate-300 border-slate-700',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold border {$classes}"]) }}>
    {{ $slot }}
</span>
