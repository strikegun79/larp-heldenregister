{{-- Hauptnavigation (Waldritter v1): heller Text auf Waldgrün, aktiver Punkt mit goldener Unterstreichung
     und aria-current="page" (nicht nur Farbe). --}}
@props(['active'])

<a {{ $attributes->class(['wr-nav__link', 'wr-nav__link--active' => $active ?? false]) }}
   @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
