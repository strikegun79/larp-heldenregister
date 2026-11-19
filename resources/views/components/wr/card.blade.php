{{-- Waldritter v1: Elfenbein-Karte mit optionalem Bild, Kategoriezeile, Titel und Text.
     Mit "href" wird die ganze Karte zum Link – dann darf der Slot keine weiteren
     Links oder Buttons enthalten (verschachtelte interaktive Elemente). Für Karten
     mit eigenen Aktionen "href" weglassen und den Slot "actions" nutzen. --}}
@props([
    'href' => null,
    'external' => false,
    'image' => null,
    'imageAlt' => '',
    'tag' => null,
    'title' => null,
    'text' => null,
    'headingLevel' => 'h3',
])

@php
    $tagName = $href ? 'a' : 'article';
@endphp

<{{ $tagName }}
    @if ($href) href="{{ $href }}" @if ($external) target="_blank" rel="noopener" @endif @endif
    {{ $attributes->class(['wr-card', 'wr-card--link' => $href]) }}>

    @if ($image)
        <div class="wr-card__media save-data-hide">
            <img src="{{ $image }}" alt="{{ $imageAlt }}" @if ($imageAlt === '') aria-hidden="true" @endif
                 loading="lazy" width="400" height="176">
        </div>
    @endif

    <div class="wr-card__body">
        @if ($tag)
            <span class="wr-tag">{{ $tag }}</span>
        @endif
        @if ($title)
            <{{ $headingLevel }} class="wr-card__title">{{ $title }}</{{ $headingLevel }}>
        @endif
        @if ($text)
            <p class="wr-card__text">{{ $text }}</p>
        @endif

        {{ $slot }}

        @isset($actions)
            <div class="wr-card__actions">
                {{ $actions }}
            </div>
        @endisset
    </div>
</{{ $tagName }}>
