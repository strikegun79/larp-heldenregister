@can('manage-attendance')
    @if ($adventure->bookings->isEmpty())
        <p class="text-stone-500">Keine Anmeldungen zum Abhaken.</p>
    @else
        @php($visitedIds = $adventure->visits->pluck('player_id'))
        <table class="ui very basic compact table">
            <thead><tr><th>Teilnehmer</th><th>Anwesend</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($adventure->bookings as $booking)
                    @php($present = $visitedIds->contains($booking->player_id))
                    <tr>
                        <td>{{ $booking->player?->full_name }}</td>
                        <td>@if ($present)<span class="text-green-700">✓ anwesend</span>@else<span class="text-stone-500">—</span>@endif</td>
                        <td>
                            @if ($booking->status === 'abgemeldet')
                                <span class="text-orange-600">abgemeldet{{ $booking->absence_reason_label ? ' ('.$booking->absence_reason_label.')' : '' }}</span>
                            @else
                                <span class="text-stone-500">{{ $booking->status_label }}</span>
                            @endif
                        </td>
                        <td class="right aligned">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route('adventures.bookings.checkin', [$adventure, $booking]) }}" data-refresh-modal>
                                    @csrf @method('PATCH')
                                    <button type="submit" class="ui mini {{ $present ? '' : 'primary' }} button">{{ $present ? 'auschecken' : 'Check-in' }}</button>
                                </form>
                                <form method="POST" action="{{ route('adventures.bookings.deregister', [$adventure, $booking]) }}" data-refresh-modal class="ui form">
                                    @csrf @method('PATCH')
                                    <div class="flex items-center gap-1">
                                        <select name="absence_reason" required class="!w-auto">
                                            <option value="">Grund…</option>
                                            @foreach (\App\Models\Booking::ABSENCE_REASONS as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="ui mini button">Abmelden</button>
                                    </div>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @php($days = $adventure->start_at && $adventure->end_at ? max(1, (int) $adventure->start_at->copy()->startOfDay()->diffInDays($adventure->end_at->copy()->startOfDay()) + 1) : 1)
        @php($epPerHero = $adventure->loot_ep_day * $days)
        <form method="POST" action="{{ route('adventures.award-ep', $adventure) }}" data-refresh-modal class="mt-3"
              onsubmit="return confirm('Allen anwesenden aktiven Helden je {{ $epPerHero }} EP gutschreiben?');">
            @csrf
            <button type="submit" class="ui button">EP für Teilnehmer verbuchen ({{ $epPerHero }} EP/Held)</button>
        </form>
    @endif
@endcan

@can('take-signatures')
    <h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">Unterschriften &amp; Teilnehmerliste</h3>
    <a href="{{ route('adventures.participants-pdf', $adventure) }}" class="ui button mb-3" target="_blank" rel="noopener">
        Teilnehmerliste als PDF
    </a>

    @if ($adventure->bookings->isEmpty())
        <p class="text-stone-500">Keine Anmeldungen.</p>
    @else
        <table class="ui very basic compact table">
            <thead><tr><th>Teilnehmer</th><th>Unterschrift</th><th></th></tr></thead>
            <tbody>
                @foreach ($adventure->bookings as $booking)
                    <tr>
                        <td>{{ $booking->player?->full_name }}</td>
                        <td>
                            @if ($booking->signature)
                                <span class="text-green-700">✓ unterschrieben</span>
                            @else
                                <span class="text-stone-500">offen</span>
                            @endif
                        </td>
                        <td class="right aligned">
                            <a href="{{ route('adventures.bookings.signature.edit', [$adventure, $booking]) }}"
                               data-modal-subview="{{ route('adventures.bookings.signature.edit', [$adventure, $booking]) }}"
                               class="text-waldritter hover:underline">{{ $booking->signature ? 'ändern' : 'erfassen' }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endcan
