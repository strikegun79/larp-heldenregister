{{-- Waldritter v1: Abschnittstitel mit optionalem Zusatz rechts (z. B. Anzahl, Link).
     "focusable": Titel per Skript fokussierbar (tabindex=-1), z. B. als Fokusziel nach dem Schließen eines Hinweises. --}}
@props([
    'title',
    'aside' => null,
    'id' => null,
    'level' => 'h2',
    'focusable' => false,
])

<div {{ $attributes->merge(['class' => 'wr-section-heading']) }}>
    <{{ $level }} @if ($id) id="{{ $id }}" @endif @if ($focusable) tabindex="-1" @endif class="wr-section-heading__title focus:outline-none">{{ $title }}</{{ $level }}>
    @if ($aside)
        <span class="wr-section-heading__aside">{{ $aside }}</span>
    @endif
</div>
