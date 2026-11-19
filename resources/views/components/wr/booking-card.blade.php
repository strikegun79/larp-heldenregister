{{-- Waldritter v1: Abenteuer mit den eigenen Anmeldungen. Ganze Karte verlinkt auf das Abenteuer.
     Erwartet ein Adventure mit geladenen Relationen "location" und "bookings" (inkl. "player" und "role"). --}}
@props(['adventure'])

<a href="{{ route('adventures.show', $adventure) }}"
   {{ $attributes->merge(['class' => 'wr-card wr-card--link']) }}>
    <div class="wr-card__body sm:flex-row sm:items-center sm:gap-6">
        <div class="flex-1 min-w-0 flex flex-col gap-1">
            <h3 class="wr-card__title">{{ $adventure->name }}</h3>
            <x-wr.adventure-meta :adventure="$adventure" class="text-wr-ink-muted" />
        </div>

        <div class="flex items-center gap-3 sm:justify-end">
            <x-wr.booking-participants :adventure="$adventure" />
            <i class="hidden sm:inline-block chevron right icon text-wr-ink-muted" aria-hidden="true"></i>
        </div>
    </div>
</a>
