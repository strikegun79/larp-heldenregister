<span data-modal-title hidden>Anmeldung bearbeiten · {{ $booking->participant_name }}</span>

{{-- Anmeldung bearbeiten (Waldritter v1). Lädt im gestapelten Modal; eigener .wr-v1-Wrapper.
     Feldnamen, IDs und .field-Struktur bleiben (Gesundheits-Einwilligung per Skript). --}}
<div class="wr-v1">

<p class="wr-card__text mb-3">{{ $adventure->name }} · <strong class="text-wr-ink">{{ $booking->participant_name }}</strong></p>

<form id="booking-edit-form" data-stack-close method="POST" action="{{ route('adventures.bookings.update', [$adventure, $booking]) }}"
      class="ui form">
    @csrf @method('PUT')

    <p class="wr-help mt-0 mb-4">Mit <span class="text-wr-danger font-bold" aria-hidden="true">*</span> markierte Felder sind Pflichtfelder.</p>

    <fieldset class="wr-fieldset">
        <legend>Teilnahme</legend>
        <div class="field required">
            <label for="edit-role">Rolle</label>
            <select id="edit-role" name="event_role_id" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected($booking->event_role_id == $role->id)>{{ $role->description }}</option>
                @endforeach
            </select>
        </div>
    </fieldset>

    <fieldset class="wr-fieldset">
        <legend>Gut zu wissen <span class="font-normal text-sm">(optional)</span></legend>
        <div class="grid gap-2 sm:grid-cols-2 mb-3">
            <label class="wr-check">
                <input type="checkbox" name="vegetarier" value="1" @checked($booking->vegetarier)>
                <span class="wr-check__text"><span class="wr-check__title">Vegetarisches Essen</span></span>
            </label>
            <label class="wr-check">
                <input type="checkbox" name="leih_tunika" value="1" @checked($booking->leih_tunika)>
                <span class="wr-check__text">
                    <span class="wr-check__title">Leih-Tunika</span>
                    <span class="wr-check__hint">Eine Tunika wird für die Veranstaltung ausgeliehen.</span>
                </span>
            </label>
            <label class="wr-check">
                <input type="checkbox" name="leih_waffe" value="1" @checked($booking->leih_waffe)>
                <span class="wr-check__text">
                    <span class="wr-check__title">Leih-Waffe</span>
                    <span class="wr-check__hint">Eine Waffe wird für die Veranstaltung ausgeliehen.</span>
                </span>
            </label>
            <label class="wr-check">
                <input type="checkbox" name="nsc" value="1" @checked($booking->nsc)>
                <span class="wr-check__text">
                    <span class="wr-check__title">NSC (Nichtspielercharakter)</span>
                    <span class="wr-check__hint">Übernimmt eine Nebenrolle in der Geschichte, statt mit dem eigenen Helden zu spielen.</span>
                </span>
            </label>
        </div>
    </fieldset>

    <fieldset class="wr-fieldset">
        <legend>Fotoerlaubnis <span class="font-normal text-sm">(optional)</span></legend>
        <label class="wr-check mb-3">
            <input type="checkbox" name="fotoerlaubnis" value="1" @checked($booking->fotoerlaubnis)>
            <span class="wr-check__text">
                <span class="wr-check__title">Fotos erlaubt</span>
                <span class="wr-check__hint">
                    Der Verein darf Fotos von dieser Veranstaltung verwenden, auf denen der Spieler zu sehen ist.
                    Du kannst die Erlaubnis jederzeit im Spielerprofil unter „Fotoerlaubnis“ widerrufen.
                </span>
            </span>
        </label>
    </fieldset>

    <fieldset class="wr-fieldset">
        <legend>Gesundheit und Notfall</legend>
        <p class="wr-fieldset__intro">Diese Angaben sieht nur das Organisationsteam. Sie dienen ausschließlich der Sicherheit.</p>

        <div class="field">
            <label for="edit-allergien">Allergien / Unverträglichkeiten <span class="font-normal">(optional)</span></label>
            <textarea id="edit-allergien" name="allergien" rows="2"
                      placeholder="z. B. Nüsse, Laktose, Bienen …">{{ $booking->allergien }}</textarea>
        </div>

        <div class="field">
            <label for="edit-medikamente">Medikamente <span class="font-normal">(optional)</span></label>
            <textarea id="edit-medikamente" name="medikamente" rows="2"
                      placeholder="z. B. Epipen, Inhalator, tägliche Einnahme …">{{ $booking->medikamente }}</textarea>
            <small class="wr-help">Regelmäßige Medikamente, die während der Veranstaltung benötigt werden.</small>
        </div>

        {{-- DSGVO Art. 9: Einwilligung – vorbelegt wenn bereits erteilt, Pflichtfeld wenn Gesundheitsdaten ausgefüllt --}}
        <div class="field" id="health-consent-field-edit">
            <label class="wr-check">
                <input type="checkbox" name="health_data_consent" id="health_data_consent_edit" value="1"
                       @checked($booking->health_data_consent_at !== null)>
                <span class="wr-check__text">
                    <span class="wr-check__title">
                        Einwilligung Gesundheitsdaten
                        <span data-hcr-badge hidden class="text-wr-danger font-semibold text-xs">&nbsp;* Pflichtfeld</span>
                    </span>
                    <span class="wr-check__hint">
                        Ich willige ausdrücklich ein, dass die oben angegebenen Gesundheitsdaten gemäß
                        Art. 9 Abs. 2 lit. a DSGVO gespeichert und ausschließlich zum Schutz des Kindes
                        in Notfallsituationen an das Veranstaltungsteam weitergegeben werden.
                    </span>
                </span>
            </label>
        </div>

        <div class="field">
            <label for="edit-erreichbarkeit">Erreichbarkeit während der Veranstaltung <span class="font-normal">(optional)</span></label>
            <textarea id="edit-erreichbarkeit" name="erreichbarkeit" rows="2" placeholder="z. B. Handy-Nummer vor Ort, Hotel, Zeltplatz …">{{ $booking->erreichbarkeit }}</textarea>
            <small class="wr-help">Wo kannst du kurzfristig erreicht werden, falls wir dich kontaktieren müssen?</small>
        </div>

        <div class="field required">
            <label for="edit-kontakt-telefon">Kontaktrufnummer (Notfallkontakt)</label>
            <input type="tel" id="edit-kontakt-telefon" name="kontakt_telefon" maxlength="100" required
                   value="{{ old('kontakt_telefon', $booking->kontakt_telefon ?? $userPhone ?? '') }}"
                   placeholder="z. B. +49 123 456789">
            @if ($booking->kontakt_telefon)
                <small class="wr-help">Diese Nummer wird im Notfall kontaktiert.</small>
            @elseif ($userPhone)
                <small class="wr-help">Aus deinem Profil übernommen – du kannst die Nummer für dieses Event ändern.</small>
            @else
                <small class="wr-help">Diese Nummer wird im Notfall kontaktiert.</small>
            @endif
        </div>
    </fieldset>

    @if ($adventure->fee_reduced !== null && $adventure->fee > 0)
        <fieldset class="wr-fieldset">
            <legend>Beitrag</legend>
            <div class="field">
                <label class="wr-check">
                    <input type="checkbox" name="ermaessigung" value="1" @checked($booking->ermaessigung)>
                    <span class="wr-check__text">
                        <span class="wr-check__title">
                            Ermäßigung
                            <span class="font-normal">({{ number_format($adventure->fee_reduced, 2, ',', '.') }} € statt {{ number_format($adventure->fee, 2, ',', '.') }} €)</span>
                        </span>
                        <span class="wr-check__hint">
                            Die Ermäßigung muss beim Check-in nachgewiesen werden, z.&nbsp;B. durch einen Bescheid über
                            Grundsicherung (SGB II/XII), Kinderzuschlag oder Wohngeld.
                        </span>
                    </span>
                </label>
            </div>
        </fieldset>
    @endif
</form>

<div data-modal-actions hidden>
    <button type="submit" form="booking-edit-form" class="wr-btn wr-btn--primary">Speichern</button>
</div>
</div>
