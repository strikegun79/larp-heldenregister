{{-- Waldritter v1: Heldenkarte (Bild, Name, Klassen, verfügbare EP, nächste Aktion).
     Ganze Karte verlinkt auf das Heldenprofil. Erwartet einen Helden mit geladenen
     Relationen "classes" und "epTransactions" (für ep_balance). --}}
@props([
    'hero',
    'label' => null,
])

<a href="{{ route('heroes.show', $hero) }}"
   {{ $attributes->merge(['class' => 'wr-card wr-card--link']) }}>
    <div class="wr-card__body flex-row items-center gap-4">
        <img src="{{ $hero->image_url }}" alt="" aria-hidden="true" loading="lazy"
             class="w-20 h-20 shrink-0 object-cover rounded-lg border border-wr-border">

        <div class="flex-1 min-w-0 flex flex-col gap-1">
            @if ($label)
                <span class="wr-tag">{{ $label }}</span>
            @endif
            <h3 class="wr-card__title truncate">{{ $hero->character_name }}</h3>

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
                {{ number_format($hero->ep_balance, 0, ',', '.') }} EP verfügbar
            </span>
        </div>

        <span class="hidden sm:inline-flex items-center gap-1 shrink-0 text-sm font-semibold text-wr-forest" aria-hidden="true">
            Zum Heldenbuch <i class="chevron right icon"></i>
        </span>
        <i class="sm:hidden chevron right icon shrink-0 text-wr-ink-muted" aria-hidden="true"></i>
    </div>
</a>
