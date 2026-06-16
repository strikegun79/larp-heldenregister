<span data-modal-title hidden>Anmelden · {{ $adventure->name }}</span>

@if (! $adventure->registrationOpen())
    <p class="text-stone-500">Die Anmeldung ist derzeit nicht geöffnet (Status: {{ $adventure->status?->description }}).</p>
@elseif ($players->isEmpty())
    <p class="text-stone-500">Alle wählbaren Spieler sind für dieses Abenteuer bereits angemeldet.</p>
@else
    @if ($adventure->isFull())
        <p class="mb-3 text-orange-600">Das Abenteuer ist voll – neue Anmeldungen kommen auf die Warteliste.</p>
    @endif

    <form id="booking-create-form" data-stack-close method="POST" action="{{ route('adventures.bookings.store', $adventure) }}" class="ui form">
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

        {{-- Kontaktrufnummer: Pflichtfeld für den Notfall. Vorausgefüllt aus dem Nutzerprofil. --}}
        <div class="field required">
            <label>Kontaktrufnummer (Notfallkontakt)</label>
            <input type="tel" name="kontakt_telefon" maxlength="100" required
                   value="{{ old('kontakt_telefon', $userPhone ?? '') }}"
                   placeholder="z. B. +49 123 456789">
            @if ($userPhone)
                <small class="text-stone-400">Aus deinem Profil übernommen – du kannst die Nummer ändern.</small>
            @endif
        </div>

        <label class="flex items-center gap-2 my-2"><input type="checkbox" name="agb" value="1" required> Ich akzeptiere die AGB</label>
    </form>

    <div data-modal-actions hidden>
        <button type="submit" form="booking-create-form" class="ui primary button">Anmeldung absenden</button>
    </div>
@endif
