{{-- Heldenbuch – Abenteuer (Waldritter v1). Einmal geschrieben für Akkordeon und Reiter.
     Listen statt Tabellen, damit es auf dem Handy lesbar bleibt. Erwartet: $hero. --}}
@php
    $bookings = $hero->player?->bookings?->sortByDesc(fn ($b) => optional($b->adventure)->start_at) ?? collect();
@endphp

<div class="space-y-6">
    <section>
        <h3 class="wr-section-heading__title text-lg mb-2">Bestrittene Abenteuer</h3>
        @if ($hero->adventure_history->isEmpty())
            <p class="text-wr-ink-muted m-0">Noch keine Abenteuer bestritten.</p>
        @else
            <ul class="wr-rows" role="list">
                @foreach ($hero->adventure_history as $tx)
                    <li class="wr-row">
                        <div class="min-w-0">
                            <span class="font-semibold break-words">{{ $tx->adventure?->name ?? '—' }}</span>
                            <span class="block text-sm text-wr-ink-muted">{{ optional($tx->transacted_at)->format('d.m.Y') }}</span>
                        </div>
                        <span class="wr-pill wr-pill--success shrink-0">
                            <i class="plus icon" aria-hidden="true"></i>{{ number_format($tx->ep_count, 0, ',', '.') }} EP erhalten
                        </span>
                    </li>
                @endforeach
            </ul>
            <p class="text-sm font-semibold mt-2 mb-0 text-right">
                EP aus Abenteuern gesamt: {{ number_format($hero->adventures_ep_total, 0, ',', '.') }}
            </p>
        @endif
    </section>

    <section>
        <h3 class="wr-section-heading__title text-lg mb-1">Anmeldungen des Spielers</h3>
        <p class="text-sm text-wr-ink-muted mb-2">Anmeldungen gelten für den Spieler, also für alle seine Helden.</p>
        @if ($bookings->isEmpty())
            <p class="text-wr-ink-muted m-0">Keine Anmeldungen.</p>
        @else
            <ul class="wr-rows" role="list">
                @foreach ($bookings as $booking)
                    <li class="wr-row">
                        <div class="min-w-0">
                            <span class="font-semibold break-words">{{ $booking->adventure?->name ?? '—' }}</span>
                            <span class="block text-sm text-wr-ink-muted">
                                @if ($booking->adventure?->start_at)
                                    <x-wr.adventure-date :adventure="$booking->adventure" />
                                @else
                                    Termin offen
                                @endif
                            </span>
                        </div>
                        @if ($booking->waitlisted)
                            <span class="wr-pill wr-pill--waitlist shrink-0"><i class="hourglass half icon" aria-hidden="true"></i>Warteliste</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
