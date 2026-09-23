@props(['name' => 'arrow'])
<svg {{ $attributes->merge(['class' => 'tm-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('search') <circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/> @break
        @case('cart') <path d="M3 3h2l3 12h11l2-8H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/> @break
        @case('shield') <path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="m8 12 3 3 5-6"/> @break
        @case('game') <path d="M7 7h10c3 0 5 11 2 12-2 1-4-3-5-3h-4c-1 0-3 4-5 3C2 18 4 7 7 7ZM7 10v4m-2-2h4m6-1h.01m3 3h.01"/> @break
        @case('spark') <path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3Z"/> @break
        @case('bolt') <path d="m13 2-9 12h7l-1 8 10-13h-7l1-7Z"/> @break
        @case('grid') <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/> @break
        @case('user') <circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/> @break
        @case('store') <path d="M4 10v11h16V10M3 10l2-7h14l2 7M3 10c0 4 5 4 5 0 0 4 8 4 8 0 0 4 5 4 5 0M9 21v-7h6v7"/> @break
        @case('chevron') <path d="m9 5 7 7-7 7"/> @break
        @default <path d="M4 12h16m-6-6 6 6-6 6"/>
    @endswitch
</svg>
