@can('manage-checkin')
    <a href="{{ route('adventures.participants-pdf', $adventure) }}" class="ui button mb-3" target="_blank" rel="noopener">
        Teilnehmerliste als PDF
    </a>

    @unless ($adventure->checkinAllowed())
        <div class="ui warning message" style="display:block">
            Check-in ist erst ab Status „Anmeldung geschlossen" möglich (aktuell: {{ $adventure->status?->description }}).
        </div>
    @endunless

    @php($checkinBookings = $adventure->bookings->whereNotNull('player_id'))
    @if ($checkinBookings->isEmpty())
        <p class="text-stone-500">Keine Anmeldungen mit Check-in (Gäste sammeln keine EP).</p>
    @else
        @php($visitedIds = $adventure->visits->pluck('player_id'))
        <table class="ui very basic compact table">
            <thead><tr><th>Teilnehmer</th><th>Status</th><th>Unterschrift</th><th></th></tr></thead>
            <tbody>
                @foreach ($checkinBookings as $booking)
                    @php($present = $visitedIds->contains($booking->player_id))
                    <tr>
                        <td>{{ $booking->player?->full_name }}</td>
                        <td>
                            @if ($booking->status === 'abgemeldet')
                                <span class="text-orange-600">abgemeldet{{ $booking->absence_reason_label ? ' ('.$booking->absence_reason_label.')' : '' }}</span>
                            @elseif ($present)
                                <span class="text-green-700">✓ anwesend</span>
                            @else
                                <span class="text-stone-500">{{ $booking->status_label }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($booking->signature)
                                <img src="{{ $booking->signature }}" alt="Unterschrift" style="height:34px; background:#fff; border:1px solid #ccc">
                            @else
                                <span class="text-stone-500">—</span>
                            @endif
                        </td>
                        <td class="right aligned">
                            <div class="flex items-center justify-end gap-2">
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

        @if ($adventure->checkinAllowed())
            @php($days = $adventure->start_at && $adventure->end_at ? max(1, (int) $adventure->start_at->copy()->startOfDay()->diffInDays($adventure->end_at->copy()->startOfDay()) + 1) : 1)
            @php($epPerHero = $adventure->loot_ep_day * $days)
            <form method="POST" action="{{ route('adventures.award-ep', $adventure) }}" data-refresh-modal class="mt-3"
                  data-confirm="Allen anwesenden aktiven Helden je {{ $epPerHero }} EP gutschreiben? Die EP landen im EP-Verlauf des aktiven Helden.">
                @csrf
                <button type="submit" class="ui button">EP für Teilnehmer verbuchen ({{ $epPerHero }} EP/Held)</button>
            </form>
        @endif
    @endif
@endcan
