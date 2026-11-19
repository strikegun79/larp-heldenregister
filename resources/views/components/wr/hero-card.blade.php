{{-- Waldritter v1: Heldenkarte (Bild, Name, Klassen, verfügbare Erfahrungspunkte, nächste Aktion).
     Ganze Karte verlinkt auf das Heldenprofil. Erwartet einen Helden mit geladenen
     Relationen "classes" und "epTransactions" (für ep_balance).
     Sehr schmale Bildschirme (< 400 px): Bild über dem Text, lange Namen werden umbrochen. --}}
@props([
    'hero',
    'label' => null,
])

<a href="{{ route('heroes.show', $hero) }}"
   {{ $attributes->merge(['class' => 'wr-card wr-card--link']) }}>
    <div class="wr-card__body min-[400px]:flex-row min-[400px]:items-center gap-3 min-[400px]:gap-4">
        <img src="{{ $hero->image_url }}" alt="" aria-hidden="true" loading="lazy"
             class="w-20 h-20 shrink-0 object-cover rounded-lg border border-wr-border">

        <div class="flex-1 min-w-0 flex flex-col gap-1">
            @if ($label)
                <span class="wr-tag">{{ $label }}</span>
            @endif
            <h3 class="wr-card__title [overflow-wrap:anywhere]">{{ $hero->character_name }}</h3>

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

        <span class="inline-flex items-center gap-1 shrink-0 text-sm font-semibold text-wr-forest" aria-hidden="true">
            <span class="sm:hidden">Heldenbuch öffnen</span>
            <span class="hidden sm:inline">Zum Heldenbuch</span>
            <i class="chevron right icon"></i>
        </span>
    </div>
</a>
