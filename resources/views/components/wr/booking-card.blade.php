{{-- Waldritter v1: Abenteuer mit den eigenen Anmeldungen.
     Pro Teilnehmer: Name plus Status als sichtbarer Text mit eigenem Symbol
     (nie nur Farbe oder Tooltip). Ganze Karte verlinkt auf das Abenteuer.
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
            <ul class="wr-participants" role="list">
                @foreach ($adventure->bookings as $booking)
                    @php
                        if ($booking->waitlisted) {
                            [$pillClass, $icon, $statusText] = ['wr-pill--waitlist', 'hourglass half', 'Warteliste'];
                        } elseif ($booking->status === 'bestaetigt') {
                            [$pillClass, $icon, $statusText] = ['wr-pill--success', 'check circle', 'bestätigt'];
                        } else {
                            [$pillClass, $icon, $statusText] = ['wr-pill--pending', 'clock outline', 'wartet auf Bestätigung'];
                        }
                    @endphp
                    <li class="wr-participant">
                        <span class="wr-participant__name">{{ $booking->participant_name }}:</span>
                        <span class="wr-pill wr-pill--small {{ $pillClass }}">
                            <i class="{{ $icon }} icon" aria-hidden="true"></i>{{ $statusText }}
                        </span>

                        @if ($booking->role?->is_teamer_like)
                            <span class="wr-pill wr-pill--small wr-pill--info">
                                <i class="shield alternate icon" aria-hidden="true"></i>Teamer
                            </span>
                        @elseif ($adventure->fee > 0 && ! $booking->waitlisted)
                            @if ($booking->paid)
                                <span class="wr-pill wr-pill--small wr-pill--success">
                                    <i class="money bill alternate icon" aria-hidden="true"></i>bezahlt
                                </span>
                            @else
                                <span class="wr-pill wr-pill--small wr-pill--pending">
                                    <i class="euro sign icon" aria-hidden="true"></i>Beitrag offen
                                </span>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
            <i class="hidden sm:inline-block chevron right icon text-wr-ink-muted" aria-hidden="true"></i>
        </div>
    </div>
</a>
