{{-- Waldritter v1: Abschnittstitel mit optionalem Zusatz rechts (z. B. Anzahl, Link). --}}
@props([
    'title',
    'aside' => null,
    'id' => null,
    'level' => 'h2',
])

<div {{ $attributes->merge(['class' => 'wr-section-heading']) }}>
    <{{ $level }} @if ($id) id="{{ $id }}" @endif class="wr-section-heading__title">{{ $title }}</{{ $level }}>
    @if ($aside)
        <span class="wr-section-heading__aside">{{ $aside }}</span>
    @endif
</div>
