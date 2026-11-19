{{-- Waldritter v1: Heldenkarte (Bild, Name, Klassen, verfügbare Erfahrungspunkte, nächste Aktion).
     Ganze Karte verlinkt auf das Heldenprofil. Erwartet einen Helden mit geladenen
     Relationen "classes" und "epTransactions" (für ep_balance).
     Smartphone: Bild + Text nebeneinander, „Heldenbuch öffnen“ als eigene Zeile darunter,
     damit der Text die volle Breite neben dem Bild behält. Ab 640 px steht der Link rechts. --}}
@props([
    'hero',
    'label' => null,
])

<a href="{{ route('heroes.show', $hero) }}"
   {{ $attributes->merge(['class' => 'wr-card wr-card--link']) }}>
    <div class="wr-card__body sm:flex-row sm:items-center gap-3 sm:gap-4">
        <div class="flex flex-1 min-w-0 items-start sm:items-center gap-3 sm:gap-4">
            <img src="{{ $hero->image_url }}" alt="" aria-hidden="true" loading="lazy"
                 class="w-16 h-16 sm:w-20 sm:h-20 shrink-0 object-cover rounded-lg border border-wr-border">

            <div class="flex-1 min-w-0 flex flex-col gap-1">
                @if ($label)
                    <span class="wr-tag">{{ $label }}</span>
                @endif
                {{-- Umbruch nur an Wortgrenzen bzw. per Silbentrennung; überlange Einzelwörter notfalls hart --}}
                <h3 class="wr-card__title break-words hyphens-auto">{{ $hero->character_name }}</h3>

                @if ($hero->classes->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($hero->classes as $heroClass)
                            <x-wr.class-badge :hero-class="$heroClass" />
                        @endforeach
                    </div>
                @else
                    <span class="wr-card__text">Noch keine Klasse</span>
                @endif

                <span class="text-sm font-semibold text-wr-forest">
                    {{ number_format($hero->ep_balance, 0, ',', '.') }} Erfahrungspunkte (EP) verfügbar
                </span>

                {{ $slot }}
            </div>
        </div>

        <span class="inline-flex items-center justify-end gap-1 shrink-0 text-sm font-semibold text-wr-forest
                     pt-2 border-t border-wr-border sm:pt-0 sm:border-t-0" aria-hidden="true">
            <span class="sm:hidden">Heldenbuch öffnen</span>
            <span class="hidden sm:inline">Zum Heldenbuch</span>
            <i class="chevron right icon"></i>
        </span>
    </div>
</a>
