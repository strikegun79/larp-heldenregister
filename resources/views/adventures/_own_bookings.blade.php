{{-- Eigene Anmeldungen in der Abenteuer-Detailansicht (Waldritter v1): eigene Spieler und
     selbst angemeldete Gäste (Auswahl im Controller). Die Verwaltung nutzt weiterhin _bookings. --}}
{{-- Issue #19: eigene Teamer-Anmeldung mit Abrechnung der Aufwandsentschädigung --}}
@if ($myTeamerSignup ?? null)
    @php
        $tsBlocker = $myTeamerSignup->compensationBlocker();
        $tsAmount = $tsBlocker === null ? $myTeamerSignup->compensationAmount() : null;
        $tsMissing = auth()->user()->missingTeamerBillingFields();
        [$tsPill, $tsIcon] = match ($myTeamerSignup->status_label) {
            'bestätigt' => ['wr-pill--success', 'check circle'],
            'abgelehnt' => ['wr-pill--danger', 'times circle'],
            default => ['wr-pill--pending', 'clock'],
        };
    @endphp
    <div class="wr-card mb-4">
        <div class="wr-card__body gap-2">
            <span class="wr-card__title">Deine Teamer-Anmeldung</span>
            <div class="flex flex-wrap gap-1.5">
                <span class="wr-pill {{ $tsPill }}"><i class="{{ $tsIcon }} icon" aria-hidden="true"></i>{{ $myTeamerSignup->status_label }}</span>
                <span class="wr-pill wr-pill--info"><i class="shield alternate icon" aria-hidden="true"></i>{{ $myTeamerSignup->eventRole?->description ?? 'Rolle noch offen' }}</span>
                @if ($tsAmount !== null)
                    <span class="wr-pill"><i class="euro sign icon" aria-hidden="true"></i>Aufwandsentschädigung {{ number_format((float) $tsAmount, 2, ',', '.') }} €</span>
                @endif
            </div>

            @if ($myTeamerSignup->status_label !== 'abgelehnt')
                @if ($tsBlocker)
                    <p class="wr-help m-0">{{ $tsBlocker }}</p>
                @elseif ($tsMissing)
                    <p class="wr-help m-0">
                        Für die Abrechnung fehlt in deinem Profil noch: {{ implode(', ', $tsMissing) }}.
                        <a href="{{ route('profile.edit') }}#teamer" class="wr-interactive underline text-wr-forest font-semibold">Zum Profil</a>
                    </p>
                @else
                    <div>
                        <a href="{{ route('adventures.teamer.compensation', [$adventure, $myTeamerSignup]) }}"
                           class="wr-btn wr-btn--secondary wr-btn--small" download>
                            <i class="file pdf icon m-0" aria-hidden="true"></i> Abrechnung herunterladen (PDF)
                        </a>
                        <p class="wr-help mt-1 mb-0">Bitte ausdrucken und unterschreiben.</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endif

@if ($visibleBookings->isEmpty())
    <p class="wr-card__text m-0">Du hast für dieses Abenteuer noch niemanden angemeldet.</p>
@else
    <ul class="space-y-3 m-0 p-0 list-none" role="list">
        @foreach ($visibleBookings as $booking)
            <x-wr.own-booking :booking="$booking" :adventure="$adventure" />
        @endforeach
    </ul>
@endif
