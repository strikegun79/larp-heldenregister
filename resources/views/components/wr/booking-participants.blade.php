{{-- Waldritter v1: eigene Teilnehmer eines Abenteuers mit Anmeldestatus.
     Pro Teilnehmer: Name plus Status als sichtbarer Text mit eigenem Symbol
     (nie nur Farbe oder Tooltip). Genutzt in Anmeldekarte und Quest-Banner.
     Erwartet ein Adventure mit geladenen "bookings" (inkl. "player" und "role"). --}}
@props(['adventure'])

<ul {{ $attributes->merge(['class' => 'wr-participants']) }} role="list">
    @foreach ($adventure->bookings as $booking)
        @php
            if ($booking->waitlisted) {
                [$pillClass, $icon, $statusText] = ['wr-pill--waitlist', 'hourglass half', 'Warteliste'];
            } elseif ($booking->status === 'bestaetigt') {
                [$pillClass, $icon, $statusText] = ['wr-pill--success', 'check circle', 'bestätigt'];
            } else {
                [$pillClass, $icon, $statusText] = ['wr-pill--pending', 'clock', 'wartet auf Bestätigung'];
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

{{-- Issue #5: Bei offenem Beitrag erklären, bis wann Überweisungen erfasst sind --}}
@if ($adventure->fee > 0 && $adventure->payments_reconciled_at
    && $adventure->bookings->contains(fn ($b) => ! $b->paid && ! $b->waitlisted && ! $b->role?->is_teamer_like))
    <x-wr.payments-reconciled-hint :date="$adventure->payments_reconciled_at" class="mt-2" />
@endif
