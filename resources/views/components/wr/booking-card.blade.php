{{-- Waldritter v1: Abenteuer mit den eigenen Anmeldungen (Status-Pills mit Symbol und Text).
     Ganze Karte verlinkt auf das Abenteuer. Erwartet ein Adventure mit geladenen
     Relationen "location" und "bookings" (inkl. "player" und "role"). --}}
@props(['adventure'])

<a href="{{ route('adventures.show', $adventure) }}"
   {{ $attributes->merge(['class' => 'wr-card wr-card--link']) }}>
    <div class="wr-card__body sm:flex-row sm:items-center sm:gap-6">
        <div class="flex-1 min-w-0 flex flex-col gap-1">
            <h3 class="wr-card__title">{{ $adventure->name }}</h3>
            <x-wr.adventure-meta :adventure="$adventure" class="text-wr-ink-muted" />
        </div>

        <div class="flex flex-wrap items-center gap-1.5 sm:justify-end">
            @foreach ($adventure->bookings as $booking)
                @php
                    if ($booking->waitlisted) {
                        [$pillClass, $statusText] = ['wr-pill--waitlist', 'Warteliste'];
                    } elseif ($booking->status === 'bestaetigt') {
                        [$pillClass, $statusText] = ['wr-pill--success', 'Bestätigt'];
                    } else {
                        [$pillClass, $statusText] = ['wr-pill--pending', 'Angemeldet'];
                    }
                @endphp
                <span class="wr-pill {{ $pillClass }}" title="{{ $statusText }}">
                    @if ($booking->waitlisted)
                        <i class="hourglass half icon" aria-hidden="true"></i>
                    @else
                        <span aria-hidden="true">&#10003;</span>
                    @endif
                    <span class="sr-only">{{ $statusText }}:</span>
                    {{ $booking->participant_name }}

                    @if ($booking->role?->is_teamer_like)
                        <i class="shield alternate icon wr-pill__icon--teamer" title="Teamer" aria-hidden="true"></i>
                        <span class="sr-only">(Teamer)</span>
                    @elseif ($adventure->fee > 0 && ! $booking->waitlisted)
                        @if ($booking->paid)
                            <i class="money bill alternate icon wr-pill__icon--paid" title="Bezahlt" aria-hidden="true"></i>
                            <span class="sr-only">(bezahlt)</span>
                        @else
                            <i class="clock icon wr-pill__icon--unpaid" title="Zahlung ausstehend" aria-hidden="true"></i>
                            <span class="sr-only">(Zahlung ausstehend)</span>
                        @endif
                    @endif
                </span>
            @endforeach
            <i class="hidden sm:inline-block chevron right icon text-wr-ink-muted" aria-hidden="true"></i>
        </div>
    </div>
</a>
