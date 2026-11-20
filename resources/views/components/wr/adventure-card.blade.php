{{-- Waldritter v1: Abenteuerkarte für Listen (Kategorie, Titel, Termin/Ort/Beitrag,
     Anmeldestatus, Alter, optional eigene Anmeldungen).
     Ganze Karte ist ein echter Link auf die Detailseite (Mittelklick/neuer Tab funktionieren).
     Die Klasse "wr-adventure-link" erlaubt der Seite, ab 640 px stattdessen das Modal zu öffnen.
     Erwartet "location", "category" und "occupied_slots_count"; eigene Buchungen nur,
     wenn die Relation "bookings" (bereits auf eigene Spieler eingeschränkt) geladen ist. --}}
@props([
    'adventure',
    'headingLevel' => 'h3',
])

@php
    $category = $adventure->category && $adventure->category->id ? $adventure->category->name : null;
    $ownBookings = $adventure->relationLoaded('bookings') ? $adventure->bookings : collect();
@endphp

<a href="{{ route('adventures.show', $adventure) }}"
   {{ $attributes->merge(['class' => 'wr-card wr-card--link wr-adventure-link']) }}>
    <div class="wr-card__body">
        @if ($category)
            <span class="wr-tag">{{ $category }}</span>
        @endif
        <{{ $headingLevel }} class="wr-card__title break-words hyphens-auto">{{ $adventure->name }}</{{ $headingLevel }}>
        <x-wr.adventure-meta :adventure="$adventure" class="text-wr-ink-muted" />

        <div class="flex flex-wrap items-center gap-1.5 mt-2">
            <x-wr.adventure-status :adventure="$adventure" />
            @if ($adventure->min_age || $adventure->max_age)
                <span class="wr-pill">
                    <i class="child icon" aria-hidden="true"></i>{{ $adventure->age_range_label }}
                </span>
            @endif
        </div>

        @if ($ownBookings->isNotEmpty())
            <div class="mt-3 pt-3 border-t border-wr-border">
                <p class="text-sm font-semibold m-0 mb-1.5">Deine Anmeldungen</p>
                <x-wr.booking-participants :adventure="$adventure" />
            </div>
        @endif
    </div>
</a>
