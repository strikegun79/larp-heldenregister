<span data-modal-title hidden>{{ $adventure->name }}</span>

<dl class="grid grid-cols-2 gap-4 text-stone-800">
    <div><dt class="text-sm text-stone-500">Beginn</dt><dd>{{ optional($adventure->start_at)->format('d.m.Y H:i') }}</dd></div>
    <div><dt class="text-sm text-stone-500">Ende</dt><dd>{{ optional($adventure->end_at)->format('d.m.Y H:i') }}</dd></div>
    <div><dt class="text-sm text-stone-500">Ort</dt><dd>{{ $adventure->location?->titel ?? '—' }}</dd></div>
    <div><dt class="text-sm text-stone-500">Status</dt><dd>{{ $adventure->status?->description }}</dd></div>
    <div><dt class="text-sm text-stone-500">Kategorie</dt><dd>{{ $adventure->category?->name }}</dd></div>
    <div><dt class="text-sm text-stone-500">Auftraggeber</dt><dd>{{ $adventure->client?->name }}</dd></div>
    <div><dt class="text-sm text-stone-500">Beitrag</dt><dd>{{ number_format($adventure->fee, 2, ',', '.') }} €</dd></div>
    <div><dt class="text-sm text-stone-500">Belegung</dt><dd class="font-semibold">{{ $adventure->confirmedBookings()->count() }} / {{ $adventure->max_player }} ({{ $adventure->freeSlots() }} frei)</dd></div>
    @if ($adventure->function_email)
        <div><dt class="text-sm text-stone-500">Funktions-E-Mail</dt><dd><a href="mailto:{{ $adventure->function_email }}" class="text-waldritter hover:underline">{{ $adventure->function_email }}</a></dd></div>
    @endif
</dl>

<h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">Anmeldungen</h3>
<table class="ui very basic compact table">
    <thead><tr><th>Spieler</th><th>Rolle</th><th>Liste</th><th>Status</th><th>Beitrag</th><th></th></tr></thead>
    <tbody>
        @forelse ($visibleBookings as $booking)
            <tr>
                <td>{{ $booking->player?->full_name }}</td>
                <td>{{ $booking->role?->description }}</td>
                <td>{{ $booking->waitlisted ? 'Warteliste' : 'regulär' }}</td>
                <td>
                    @if ($booking->approved_at)
                        <span class="text-green-700">✓ bestätigt</span>
                    @else
                        <span class="text-stone-500">offen</span>
                    @endif
                </td>
                <td>
                    @if ($booking->paid)
                        <span class="text-green-700">bezahlt</span>
                    @else
                        <span class="text-stone-500">offen</span>
                    @endif
                </td>
                <td class="right aligned">
                    <div class="flex items-center justify-end gap-3">
                        @can('approve-bookings')
                            <form method="POST" action="{{ route('adventures.bookings.approval', [$adventure, $booking]) }}" data-refresh-modal>
                                @csrf @method('PATCH')
                                <button class="{{ $booking->approved_at ? 'text-stone-600' : 'text-green-700' }} hover:underline">
                                    {{ $booking->approved_at ? 'zurücknehmen' : 'bestätigen' }}
                                </button>
                            </form>
                        @endcan
                        @can('manage-payments')
                            <form method="POST" action="{{ route('adventures.bookings.payment', [$adventure, $booking]) }}" data-refresh-modal>
                                @csrf @method('PATCH')
                                <button class="{{ $booking->paid ? 'text-stone-600' : 'text-green-700' }} hover:underline">
                                    {{ $booking->paid ? 'als offen' : 'als bezahlt' }}
                                </button>
                            </form>
                        @endcan
                        @can('adventure.modify')
                            <a href="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                               data-modal-subview="{{ route('adventures.bookings.edit', [$adventure, $booking]) }}"
                               class="text-waldritter hover:underline">bearbeiten</a>
                        @endcan
                        @can('adventure.cancel')
                            <form method="POST" action="{{ route('adventures.bookings.destroy', [$adventure, $booking]) }}"
                                  data-refresh-modal onsubmit="return confirm('Anmeldung stornieren?');">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">stornieren</button>
                            </form>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-stone-500">Noch keine Anmeldungen.</td></tr>
        @endforelse
    </tbody>
</table>

@can('manage-payments')
    @php($payable = $adventure->bookings->where('waitlisted', false))
    @php($paidCount = $payable->where('paid', true)->count())
    @php($openCount = $payable->count() - $paidCount)
    <p class="text-stone-600 -mt-2 mb-2">
        Beitrag {{ number_format($adventure->fee, 2, ',', '.') }} € · bezahlt {{ $paidCount }}/{{ $payable->count() }}
        · eingegangen {{ number_format($paidCount * $adventure->fee, 2, ',', '.') }} €
        · offen {{ number_format($openCount * $adventure->fee, 2, ',', '.') }} €
    </p>
@endcan

@can('manage-attendance')
    <h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">Teilnahme (Check-in)</h3>
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
@endcan

@can('adventure.book')
    <h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">Anmelden</h3>
    @if (! $adventure->registrationOpen())
        <p class="text-stone-500">Die Anmeldung ist derzeit nicht geöffnet (Status: {{ $adventure->status?->description }}).</p>
    @else
        @if ($adventure->isFull())
            <p class="mb-3 text-orange-600">Das Abenteuer ist voll – neue Anmeldungen kommen auf die Warteliste.</p>
        @endif
        {{-- Anmeldeformular als Unteransicht öffnen; nach dem Absenden landet man
             via refresh_modal wieder auf diesem Event-Detail (ADV-15). --}}
        <a href="{{ route('adventures.bookings.create', $adventure) }}"
           data-modal-subview="{{ route('adventures.bookings.create', $adventure) }}"
           class="ui primary button">Anmelden</a>
    @endif
@endcan

<div data-modal-actions hidden>
    @can('events.edit')
        <a href="{{ route('adventures.edit', $adventure) }}" data-modal-url="{{ route('adventures.edit', $adventure) }}" class="ui button">Bearbeiten</a>
    @endcan
</div>
