<span data-modal-title hidden>Anmelden · {{ $adventure->name }}</span>

{{-- UI-37: Kontext-Strip auf Mobile (Header scrollt weg, Info bleibt) --}}
<div class="sm:hidden flex items-center gap-2 mb-3 text-xs text-stone-400">
    <span>Abenteuer-Detail</span>
    <span aria-hidden="true">›</span>
    <span class="font-medium text-stone-600">Anmeldung</span>
</div>

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
                @if ($adventure->fee_reduced !== null)
                    <span class="text-stone-400">/ ermäßigt</span>
                    <strong class="text-emerald-700">{{ number_format($adventure->fee_reduced, 2, ',', '.') }} €</strong>
                @endif
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

@if (! $adventure->registrationOpen() && ! ($adminMode ?? false))
    <p class="text-stone-500">Die Anmeldung ist derzeit nicht geöffnet (Status: {{ $adventure->status?->description }}).</p>
@elseif ($players->isEmpty())
    <p class="text-stone-500">Alle wählbaren Spieler sind für dieses Abenteuer bereits angemeldet.</p>
@else
    @if ($adventure->isFull())
        <div class="ui warning message" style="display:block">
            Das Abenteuer ist voll – neue Anmeldungen kommen auf die Warteliste.
        </div>
    @endif

    @if ($adminMode ?? false)
    <div class="ui tiny yellow message mb-3">
        <i class="shield alternate icon"></i>
        <strong>Admin-Anmeldung:</strong> Alle Spieler sichtbar.
    </div>
    @endif

    <form id="booking-create-form" data-stack-close method="POST" action="{{ route('adventures.bookings.store', $adventure) }}" class="ui form">
        @csrf

        <p class="text-xs text-stone-400 mb-3">Mit <span class="text-red-500">*</span> markierte Felder sind Pflichtfelder.</p>

        <div class="two fields">
            <div class="field required">
                <label>Spieler</label>
                <select name="player_id" id="booking-player-select" required
                        data-prefill-url="{{ route('players.health-prefill', ['player' => '__ID__']) }}">
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
                          data-position="top left">(?)</span>
                </label>
            </div>
        </fieldset>

        @if ($adventure->fee_reduced !== null && $adventure->fee > 0)
        <div class="field mt-1">
            <label class="flex items-start gap-2 font-normal cursor-pointer">
                <input type="checkbox" name="ermaessigung" value="1" class="mt-1 shrink-0">
                <span>
                    <strong>Ermäßigung beantragen</strong>
                    <span class="text-stone-400">({{ number_format($adventure->fee_reduced, 2, ',', '.') }} € statt {{ number_format($adventure->fee, 2, ',', '.') }} €)</span>
                </span>
            </label>
            <p class="text-xs text-stone-500 mt-1 ml-6">
                Die Ermäßigung muss beim Check-in nachgewiesen werden, z.&nbsp;B. durch einen Bescheid über
                Grundsicherung (SGB II/XII), Kinderzuschlag oder Wohngeld.
                Ohne Nachweis gilt der reguläre Beitrag.
            </p>
        </div>
        @endif

        <div class="field">
            <label for="booking-allergien">Allergien / Unverträglichkeiten</label>
            <textarea id="booking-allergien" name="allergien" rows="2"
                      placeholder="z. B. Nüsse, Laktose, Bienen …"></textarea>
            <small class="text-stone-400">Optional – wird nur dem Organisationsteam angezeigt und dient ausschließlich der Sicherheit deines Kindes.</small>
        </div>

        <div class="field">
            <label for="booking-medikamente">Medikamente</label>
            <textarea id="booking-medikamente" name="medikamente" rows="2"
                      placeholder="z. B. Epipen, Inhalator, tägliche Einnahme …"></textarea>
            <small class="text-stone-400">Optional – regelmäßige Medikamente, die dein Kind während der Veranstaltung benötigt. Nur für das Orga-Team sichtbar.</small>
        </div>

        {{-- DSGVO Art. 9: Einwilligung für Gesundheitsdaten (Pflichtfeld wenn Allergien/Medikamente ausgefüllt) --}}
        <div class="field" id="health-consent-field-create">
            <label class="flex items-start gap-2 font-normal cursor-pointer">
                <input type="checkbox" name="health_data_consent" id="health_data_consent" value="1"
                       class="mt-1 shrink-0">
                <span>
                    <strong>Einwilligung Gesundheitsdaten</strong>
                    <span data-hcr-badge hidden class="text-red-600 font-semibold text-xs">&nbsp;* Pflichtfeld</span><br>
                    <span class="text-sm font-normal">
                        Ich willige ausdrücklich ein, dass die oben angegebenen Gesundheitsdaten gemäß
                        Art. 9 Abs. 2 lit. a DSGVO gespeichert und ausschließlich zum Schutz des Kindes
                        in Notfallsituationen an das Veranstaltungsteam weitergegeben werden.
                    </span>
                </span>
            </label>
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
                <span>Ich stimme den <a href="{{ route('agb') }}" target="_blank" class="text-waldritter underline">Teilnahmebedingungen</a> zu.</span>
            </label>
        </div>
    </form>

    <div data-modal-actions hidden>
        <button type="submit" form="booking-create-form" class="ui primary button">Anmeldung absenden</button>
    </div>

@endif
