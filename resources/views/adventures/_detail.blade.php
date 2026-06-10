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
</dl>

<h3 class="font-uncial text-lg text-waldritter mt-6 mb-2">Anmeldungen</h3>
<table class="ui very basic compact table">
    <thead><tr><th>Spieler</th><th>Rolle</th><th>Liste</th><th></th></tr></thead>
    <tbody>
        @forelse ($adventure->bookings as $booking)
            <tr>
                <td>{{ $booking->player?->full_name }}</td>
                <td>{{ $booking->role?->description }}</td>
                <td>{{ $booking->waitlisted ? 'Warteliste' : 'regulär' }}</td>
                <td class="right aligned">
                    @can('adventure.cancel')
                        <form method="POST" action="{{ route('adventures.bookings.destroy', [$adventure, $booking]) }}"
                              data-refresh-modal onsubmit="return confirm('Anmeldung stornieren?');">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline">stornieren</button>
                        </form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-stone-500">Noch keine Anmeldungen.</td></tr>
        @endforelse
    </tbody>
</table>

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
        <form method="POST" action="{{ route('adventures.bookings.store', $adventure) }}" class="ui form" data-refresh-modal>
            @csrf
            <div class="two fields">
                <div class="field">
                    <label>Spieler</label>
                    <select name="player_id" required>
                        <option value="">— wählen —</option>
                        @foreach ($players as $player)
                            <option value="{{ $player->id }}">{{ $player->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Rolle</label>
                    <select name="event_role_id" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->description }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 my-2">
                @foreach (['fotoerlaubnis' => 'Fotoerlaubnis', 'vegetarier' => 'Vegetarier', 'leih_tunika' => 'Leih-Tunika', 'leih_waffe' => 'Leih-Waffe', 'nsc' => 'NSC'] as $field => $label)
                    <label class="flex items-center gap-2"><input type="checkbox" name="{{ $field }}" value="1"> {{ $label }}</label>
                @endforeach
            </div>

            <div class="field">
                <label>Allergien</label>
                <textarea name="allergien" rows="2"></textarea>
            </div>

            <label class="flex items-center gap-2 my-2"><input type="checkbox" name="agb" value="1" required> Ich akzeptiere die AGB</label>

            <button type="submit" class="ui primary button">Anmeldung absenden</button>
        </form>
    @endif
@endcan

<div data-modal-actions hidden>
    @can('events.edit')
        <a href="{{ route('adventures.edit', $adventure) }}" data-modal-url="{{ route('adventures.edit', $adventure) }}" class="ui button">Bearbeiten</a>
    @endcan
</div>
