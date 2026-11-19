{{-- Waldritter v1: Quest-Banner (z. B. nächstes Abenteuer). Motiv als dekoratives Bild
     hinter einem dunklen Waldgrün-Verlauf; im Datensparmodus bleibt nur die Grundfarbe. --}}
@props([
    'title',
    'eyebrow' => null,
    'image' => '/images/abenteuer_v2.jpg',
    'headingId' => null,
    'headingLevel' => 'h2',
])

<section {{ $attributes->merge(['class' => 'wr-quest']) }}
         @if ($headingId) aria-labelledby="{{ $headingId }}" @endif>
    <div class="wr-quest__media save-data-hide">
        <img src="{{ $image }}" alt="" aria-hidden="true" loading="lazy">
    </div>

    <div class="wr-quest__content">
        @if ($eyebrow)
            <span class="wr-eyebrow wr-quest__eyebrow">{{ $eyebrow }}</span>
        @endif
        <{{ $headingLevel }} @if ($headingId) id="{{ $headingId }}" @endif class="wr-quest__title">{{ $title }}</{{ $headingLevel }}>

        {{ $slot }}
    </div>
</section>
