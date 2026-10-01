@props(['title', 'eyebrow' => 'SEU UNIVERSO GAMER', 'description' => null])

<div {{ $attributes->class(['tm-page-heading']) }}>
    <span class="tm-eyebrow"><span class="tm-status-dot" aria-hidden="true"></span>{{ $eyebrow }}</span>
    <h1>{{ $title }}</h1>
    @if ($description)
        <p>{{ $description }}</p>
    @endif
</div>
