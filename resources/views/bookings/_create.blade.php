<span data-modal-title hidden>Anmelden · {{ $adventure->name }}</span>

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

        {{-- Der teilnehmende Held ist automatisch der aktive Held des Spielers
             (HERO-21); eine Auswahl ist nicht nötig – der Bürokrat legt den
             aktiven Helden fest. --}}

        <div class="grid grid-cols-2 gap-2 my-2">
            @foreach (['fotoerlaubnis' => 'Fotoerlaubnis', 'vegetarier' => 'Vegetarier', 'leih_tunika' => 'Leih-Tunika', 'leih_waffe' => 'Leih-Waffe', 'nsc' => 'NSC'] as $field => $label)
                <label class="flex items-center gap-2"><input type="checkbox" name="{{ $field }}" value="1"> {{ $label }}</label>
            @endforeach
        </div>

        <div class="field">
            <label>Allergien</label>
            <textarea name="allergien" rows="2"></textarea>
        </div>

        <div class="field">
            <label>Medikamente</label>
            <textarea name="medikamente" rows="2"></textarea>
        </div>

        <div class="field">
            <label>Erreichbarkeit</label>
            <textarea name="erreichbarkeit" rows="2"></textarea>
        </div>

        <label class="flex items-center gap-2 my-2"><input type="checkbox" name="agb" value="1" required> Ich akzeptiere die AGB</label>

        <div class="flex items-center gap-2 mt-3">
            <button type="submit" class="ui primary button">Anmeldung absenden</button>
            <a href="{{ route('adventures.show', $adventure) }}" data-modal-subview="{{ route('adventures.show', $adventure) }}"
               class="ui basic button">Zurück</a>
        </div>
    </form>
@endif
