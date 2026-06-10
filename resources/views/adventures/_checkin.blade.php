@can('manage-attendance')
    @if ($adventure->bookings->isEmpty())
        <p class="text-stone-500">Keine Anmeldungen zum Abhaken.</p>
    @else
        @php($visitedIds = $adventure->visits->pluck('player_id'))
        <form method="POST" action="{{ route('adventures.attendance', $adventure) }}" data-refresh-modal class="ui form">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 mb-3">
                @foreach ($adventure->bookings as $booking)
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="present[]" value="{{ $booking->player_id }}" @checked($visitedIds->contains($booking->player_id))>
                        {{ $booking->player?->full_name }}
                    </label>
                @endforeach
            </div>
            <button type="submit" class="ui primary button">Teilnahme speichern</button>
        </form>

        @php($days = $adventure->start_at && $adventure->end_at ? max(1, (int) $adventure->start_at->copy()->startOfDay()->diffInDays($adventure->end_at->copy()->startOfDay()) + 1) : 1)
        @php($epPerHero = $adventure->loot_ep_day * $days)
        <form method="POST" action="{{ route('adventures.award-ep', $adventure) }}" data-refresh-modal class="mt-3"
              onsubmit="return confirm('Allen anwesenden aktiven Helden je {{ $epPerHero }} EP gutschreiben?');">
            @csrf
            <button type="submit" class="ui button">EP für Teilnehmer verbuchen ({{ $epPerHero }} EP/Held)</button>
        </form>
    @endif
@else
    <p class="text-stone-500">Keine Berechtigung für den Check-in.</p>
@endcan
