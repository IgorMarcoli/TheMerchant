@props(['title', 'description' => null, 'icon' => 'store', 'href' => null, 'action' => null])

<div {{ $attributes->class(['tm-empty tm-empty-state']) }}>
    <span class="tm-empty-symbol"><x-icon :name="$icon" /></span>
    <h2>{{ $title }}</h2>
    @if ($description)<p>{{ $description }}</p>@endif
    @if ($href && $action)<a class="tm-button" href="{{ $href }}">{{ $action }} <x-icon /></a>@endif
    {{ $slot }}
</div>
