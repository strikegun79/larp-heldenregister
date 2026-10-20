@can('manage-checkin')
    @php
        // Deutsche Sortierung nach Nachname, dann Vorname (ohne Groß-/Kleinschreibung, Umlaute korrekt).
        $collator = new \Collator('de_DE');
        $checkinBookings = $adventure->bookings
            ->whereNotNull('player_id')
            ->sort(fn ($a, $b) => $collator->compare($a->player?->lastname ?? '', $b->player?->lastname ?? '')
                ?: $collator->compare($a->player?->name ?? '', $b->player?->name ?? ''))
            ->values();
    @endphp

    <div class="flex flex-wrap items-center gap-2" style="margin-bottom:5px">
        <a href="{{ route('adventures.participants-pdf', $adventure) }}" class="ui button" target="_blank" rel="noopener">
            Teilnehmerliste als PDF
        </a>

        @if ($checkinBookings->isNotEmpty() && $adventure->checkinAllowed())
            @php($days = $adventure->start_at && $adventure->end_at ? max(1, (int) $adventure->start_at->copy()->startOfDay()->diffInDays($adventure->end_at->copy()->startOfDay()) + 1) : 1)
            @php($epPerHero = $adventure->loot_ep_day * $days)
            @php($epSummary = $adventure->epAwardSummary())
            {{-- Zeilenumbrüche im Bestätigungstext werden per white-space: pre-line dargestellt --}}
            <form method="POST" action="{{ route('adventures.award-ep', $adventure) }}" data-refresh-modal class="m-0"
                  data-confirm="Es waren {{ $epSummary['total'] }} Helden auf diesem Abenteuer.&#10;{{ $epSummary['unknown'] }} unbekannte Helden, die keine EP erhalten.&#10;{{ $epSummary['already'] }} Helden haben schon vorher ihre EP erhalten.&#10;Somit werden nun an {{ $epSummary['pending'] }} Helden jeweils {{ $epPerHero }} EP verteilt.">
                @csrf
                <button type="submit" class="ui button">EP für Teilnehmer verbuchen ({{ $epPerHero }} EP/Held)</button>
            </form>
        @endif
    </div>

    @unless ($adventure->checkinAllowed())
        <div class="ui warning message" style="display:block">
            Check-in ist erst ab Status „Anmeldung geschlossen" möglich (aktuell: {{ $adventure->status?->description }}).
        </div>
    @endunless

    @if ($checkinBookings->isEmpty())
        <p class="text-stone-500">Keine Anmeldungen mit Check-in (Gäste sammeln keine EP).</p>
    @else
        @php($visitedIds = $adventure->visits->pluck('player_id'))
        <x-mobile.cards-or-table>
            <table class="ui very basic compact table w-full">
                <thead><tr><th>Teilnehmer</th><th>Held</th><th>Status</th><th>Unterschrift</th><th></th></tr></thead>
                <tbody>
                    @foreach ($checkinBookings as $booking)
                        @php($present = $visitedIds->contains($booking->player_id))
                        <tr>
                            <td data-label="Teilnehmer" class="font-medium">{{ $booking->player?->full_name }}</td>
                            {{-- Aktiver Held: Heldenname, ersatzweise Heldenklasse(n) --}}
                            @php($activeHero = $booking->player?->activeHero)
                            <td data-label="Held">{{ $activeHero?->character_name ?: ($activeHero?->classes->pluck('name')->implode(', ') ?: '—') }}</td>
                            <td data-label="Status">
                                @if ($booking->status === 'abgemeldet')
                                    <span class="text-orange-600">abgemeldet{{ $booking->absence_reason_label ? ' ('.$booking->absence_reason_label.')' : '' }}</span>
                                @elseif ($present)
                                    <span class="text-green-700">✓ anwesend</span>
                                @else
                                    <span class="text-stone-500">{{ $booking->status_label }}</span>
                                @endif
                            </td>
                            <td data-label="Unterschrift">
                                @if ($booking->signature)
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $booking->signature }}" alt="Unterschrift" style="height:34px; background:#fff; border:1px solid #ccc" loading="lazy">
                                        <form method="POST"
                                              action="{{ route('adventures.bookings.signature.destroy', [$adventure, $booking]) }}"
                                              data-refresh-modal
                                              data-confirm="Unterschrift von {{ $booking->player?->full_name }} unwiderruflich löschen?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="ui mini basic red button" title="Unterschrift löschen (DSGVO Art. 17)">✕</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-stone-500">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2 flex-wrap">
                                    @if ($adventure->checkinAllowed())
                                        @if ($present)
                                            <form method="POST" action="{{ route('adventures.bookings.checkin', [$adventure, $booking]) }}" data-refresh-modal>
                                                @csrf @method('PATCH')
                                                <button type="submit" class="ui mini button">auschecken</button>
                                            </form>
                                        @else
                                            <button type="button" class="ui mini primary button checkin-trigger"
                                                    data-url="{{ route('adventures.bookings.signature.update', [$adventure, $booking]) }}"
                                                    data-name="{{ $booking->player?->full_name }}">Check-in</button>
                                        @endif
                                    @endif
                                    <button type="button" class="ui mini button deregister-trigger"
                                            data-url="{{ route('adventures.bookings.deregister', [$adventure, $booking]) }}"
                                            data-name="{{ $booking->player?->full_name }}">Abmelden</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-mobile.cards-or-table>
    @endif
@endcan
