<span data-modal-title hidden>Anmelden · {{ $adventure->name }}</span>

{{-- UI-31: Abenteuer-Kurzinfo vor dem Formular --}}
@php($freeSlots = $adventure->freeSlots())
<div class="bg-[#fdf6e3] border border-[#5a3a22]/20 rounded-lg px-4 py-3 mb-4 text-sm">
    <p class="font-semibold text-stone-800 mb-2">{{ $adventure->name }}</p>
    <div class="flex flex-wrap gap-x-5 gap-y-1 text-stone-600">
        @if ($adventure->start_at)
            <span><span class="text-stone-400">Datum:</span> {{ $adventure->start_at->format('d.m.Y') }}</span>
        @endif
        @if ($adventure->location)
            <span><span class="text-stone-400">Ort:</span> {{ $adventure->location->titel }}</span>
        @endif
        <span>
            <span class="text-stone-400">Beitrag:</span>
            @if ($adventure->fee > 0)
                <strong>{{ number_format($adventure->fee, 2, ',', '.') }} €</strong>
            @else
                <strong class="text-green-700">kostenlos</strong>
            @endif
        </span>
        <span>
            <span class="text-stone-400">Plätze:</span>
            {{ $adventure->max_player - $freeSlots }} / {{ $adventure->max_player }}
            @if ($freeSlots > 0)
                <span class="text-green-700">({{ $freeSlots }} frei)</span>
            @else
                <span class="text-orange-600">(Warteliste)</span>
            @endif
        </span>
    </div>
</div>

@if (! $adventure->registrationOpen())
    <p class="text-stone-500">Die Anmeldung ist derzeit nicht geöffnet (Status: {{ $adventure->status?->description }}).</p>
@elseif ($players->isEmpty())
    <p class="text-stone-500">Alle wählbaren Spieler sind für dieses Abenteuer bereits angemeldet.</p>
@else
    @if ($adventure->isFull())
        <div class="ui warning message" style="display:block">
            Das Abenteuer ist voll – neue Anmeldungen kommen auf die Warteliste.
        </div>
    @endif

    <form id="booking-create-form" data-stack-close method="POST" action="{{ route('adventures.bookings.store', $adventure) }}" class="ui form">
        @csrf

        <p class="text-xs text-stone-400 mb-3">Mit <span class="text-red-500">*</span> markierte Felder sind Pflichtfelder.</p>

        <div class="two fields">
            <div class="field required">
                <label>Spieler</label>
                <select name="player_id" required>
                    <option value="">— wählen —</option>
                    @foreach ($players as $player)
                        <option value="{{ $player->id }}">{{ $player->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field required">
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

        <fieldset class="border border-stone-200 rounded p-3 mb-3">
            <legend class="text-sm font-medium text-stone-600 px-1">Optionale Angaben</legend>
            <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="fotoerlaubnis" value="1"> Fotoerlaubnis
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="vegetarier" value="1"> Vegetarier
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="leih_tunika" value="1"> Leih-Tunika
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="leih_waffe" value="1"> Leih-Waffe
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="nsc" value="1"> NSC
                    <span class="text-stone-400 text-xs cursor-help"
                          data-tooltip="Non-Spieler-Charakter: Dein Kind übernimmt eine Statistenrolle statt als eigener Held zu spielen."
                          data-position="top center">(?)</span>
                </label>
            </div>
        </fieldset>

        <div class="field">
            <label>Allergien / Unverträglichkeiten</label>
            <textarea name="allergien" rows="2" placeholder="z. B. Nüsse, Laktose, Bienen …"></textarea>
            <small class="text-stone-400">Optional – wird nur dem Organisationsteam angezeigt und dient ausschließlich der Sicherheit deines Kindes.</small>
        </div>

        <div class="field">
            <label>Medikamente</label>
            <textarea name="medikamente" rows="2" placeholder="z. B. Epipen, Inhalator, tägliche Einnahme …"></textarea>
            <small class="text-stone-400">Optional – regelmäßige Medikamente, die dein Kind während der Veranstaltung benötigt. Nur für das Orga-Team sichtbar.</small>
        </div>

        <div class="field">
            <label>Erreichbarkeit während der Veranstaltung</label>
            <textarea name="erreichbarkeit" rows="2" placeholder="z. B. Handy-Nummer vor Ort, Hotel, Zeltplatz …"></textarea>
            <small class="text-stone-400">Optional – wo kannst du kurzfristig erreicht werden, falls wir dich kontaktieren müssen?</small>
        </div>

        <div class="field required">
            <label>Kontaktrufnummer (Notfallkontakt)</label>
            <input type="tel" name="kontakt_telefon" maxlength="100" required
                   value="{{ old('kontakt_telefon', $userPhone ?? '') }}"
                   placeholder="z. B. +49 123 456789">
            @if ($userPhone)
                <small class="text-stone-400">Aus deinem Profil übernommen – du kannst die Nummer für dieses Event ändern.</small>
            @else
                <small class="text-stone-400">Diese Nummer wird im Notfall kontaktiert.</small>
            @endif
        </div>

        <div class="field required mt-3">
            <label class="flex items-start gap-2 font-normal cursor-pointer">
                <input type="checkbox" name="agb" value="1" required class="mt-1 shrink-0">
                <span>Ich stimme den <strong>Teilnahmebedingungen</strong> zu.</span>
            </label>
            <details class="mt-1 ml-6">
                <summary class="text-xs text-waldritter cursor-pointer hover:underline">Teilnahmebedingungen anzeigen</summary>
                <div class="text-xs text-stone-600 mt-2 p-3 bg-stone-50 rounded border border-stone-200 leading-relaxed space-y-1">
                    <p>Mit der Anmeldung erkläre ich mich einverstanden, dass:</p>
                    <ul class="list-disc ml-4 space-y-1">
                        <li>Ich als Erziehungsberechtigte/r die Verantwortung für mein Kind während der Veranstaltung trage.</li>
                        <li>Mein Kind sich an die Spielregeln und Anweisungen des Organisationsteams hält.</li>
                        <li>Die angegebenen Gesundheitsdaten (Allergien, Medikamente) ausschließlich für Notfälle während der Veranstaltung verwendet werden.</li>
                        <li>Bei Abmeldung nach Anmeldeschluss die Stornierungsbedingungen des Veranstalters gelten können.</li>
                        <li>Fotos nur bei erteilter Fotoerlaubnis veröffentlicht werden.</li>
                    </ul>
                    <p class="text-stone-400 pt-1">Den vollständigen Text der Teilnahmebedingungen erhältst du beim Organisationsteam.</p>
                </div>
            </details>
        </div>
    </form>

    <div data-modal-actions hidden>
        <button type="submit" form="booking-create-form" class="ui primary button">Anmeldung absenden</button>
    </div>
@endif
