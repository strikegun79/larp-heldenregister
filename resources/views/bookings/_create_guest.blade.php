<span data-modal-title hidden>Gast anmelden · {{ $adventure->name }}</span>

{{-- Gast anmelden (Waldritter v1). Lädt im gestapelten Modal; eigener .wr-v1-Wrapper.
     Feldnamen bleiben (Validierung im BookingController). --}}
<div class="wr-v1">

{{-- UI-37: Kontext-Strip auf Mobile --}}
<div class="sm:hidden flex items-center gap-2 mb-3 text-xs text-wr-ink-muted">
    <span>Abenteuer-Detail</span>
    <span aria-hidden="true">›</span>
    <span class="font-semibold text-wr-ink">Gast anmelden</span>
</div>

@include('bookings._summary')

<div class="wr-notice mb-4" role="note">
    <i class="info circle icon m-0 mt-0.5" aria-hidden="true"></i>
    <p><strong>Hinweis:</strong> Gäste nehmen einmalig teil. Für Gäste werden <em>keine Erfahrungspunkte</em> gesammelt.</p>
</div>

@if (! $adventure->registrationOpen())
    <p class="text-wr-ink-muted">Die Anmeldung ist derzeit nicht geöffnet (Status: {{ $adventure->status?->description }}).</p>
@else
    @if ($adventure->isFull())
        <div class="wr-notice mb-4" role="note">
            <i class="hourglass half icon m-0 mt-0.5" aria-hidden="true"></i>
            <p>Das Abenteuer ist voll – Gäste kommen auf die Warteliste.</p>
        </div>
    @endif

    <form id="booking-guest-form" data-stack-close method="POST" action="{{ route('adventures.bookings.store-guest', $adventure) }}" class="ui form">
        @csrf

        <p class="wr-help mt-0 mb-4">Mit <span class="text-wr-danger font-bold" aria-hidden="true">*</span> markierte Felder sind Pflichtfelder.</p>

        <fieldset class="wr-fieldset">
            <legend>Wer nimmt teil?</legend>
            <div class="grid gap-x-4 sm:grid-cols-2">
                <div class="field required">
                    <label for="guest-name">Vorname</label>
                    <input type="text" id="guest-name" name="guest_name" required autocomplete="off">
                </div>
                <div class="field required">
                    <label for="guest-lastname">Nachname</label>
                    <input type="text" id="guest-lastname" name="guest_lastname" required autocomplete="off">
                </div>
                <div class="field">
                    <label for="guest-age">Alter <span class="font-normal">(optional)</span></label>
                    <input type="number" id="guest-age" name="guest_age" min="0" max="120">
                    <small class="wr-help">Für altersgerechte Gruppenaufteilung.</small>
                </div>
                <div class="field">
                    <label for="guest-place">Ort <span class="font-normal">(optional)</span></label>
                    <input type="text" id="guest-place" name="guest_place">
                    <small class="wr-help">Für Abholkoordination und Notfallkontakt.</small>
                </div>
            </div>
            <div class="field required">
                <label for="guest-role">Rolle</label>
                <select id="guest-role" name="event_role_id" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->description }}</option>
                    @endforeach
                </select>
            </div>
        </fieldset>

        <fieldset class="wr-fieldset">
            <legend>Gut zu wissen <span class="font-normal text-sm">(optional)</span></legend>
            <div class="grid gap-2 sm:grid-cols-2 mb-3">
                @if ($adventure->asks_vegetarian)
                    {{-- Nur wenn das Event danach fragt (Event-Einstellung „Nach vegetarischem Essen fragen“) --}}
                    <label class="wr-check">
                        <input type="checkbox" name="vegetarier" value="1">
                        <span class="wr-check__text"><span class="wr-check__title">Vegetarisches Essen</span></span>
                    </label>
                @endif
                <label class="wr-check">
                    <input type="checkbox" name="leih_tunika" value="1">
                    <span class="wr-check__text">
                        <span class="wr-check__title">Leih-Tunika</span>
                        <span class="wr-check__hint">Eine Tunika wird für die Veranstaltung ausgeliehen.</span>
                    </span>
                </label>
                <label class="wr-check">
                    <input type="checkbox" name="leih_waffe" value="1">
                    <span class="wr-check__text">
                        <span class="wr-check__title">Leih-Waffe</span>
                        <span class="wr-check__hint">Eine Waffe wird für die Veranstaltung ausgeliehen.</span>
                    </span>
                </label>
                <label class="wr-check">
                    <input type="checkbox" name="nsc" value="1">
                    <span class="wr-check__text">
                        <span class="wr-check__title">NSC (Nichtspielercharakter)</span>
                        <span class="wr-check__hint">Übernimmt eine Nebenrolle in der Geschichte, statt mit einem eigenen Helden zu spielen.</span>
                    </span>
                </label>
                <label class="wr-check">
                    <input type="checkbox" name="fotoerlaubnis" value="1">
                    <span class="wr-check__text">
                        <span class="wr-check__title">Fotos erlaubt</span>
                        <span class="wr-check__hint">Der Verein darf Fotos von dieser Veranstaltung verwenden, auf denen der Gast zu sehen ist.</span>
                    </span>
                </label>
            </div>
        </fieldset>

        <fieldset class="wr-fieldset">
            <legend>Gesundheit und Notfall</legend>
            <p class="wr-fieldset__intro">Diese Angaben sieht nur das Organisationsteam. Sie dienen ausschließlich der Sicherheit des Gastes.</p>
            <div class="field">
                <label for="guest-allergien">Allergien / Unverträglichkeiten <span class="font-normal">(optional)</span></label>
                <textarea id="guest-allergien" name="allergien" rows="2" placeholder="z. B. Nüsse, Laktose, Bienen …"></textarea>
            </div>
            <div class="field">
                <label for="guest-erreichbarkeit">Erreichbarkeit während der Veranstaltung <span class="font-normal">(optional)</span></label>
                <textarea id="guest-erreichbarkeit" name="erreichbarkeit" rows="2" placeholder="z. B. Handy-Nummer vor Ort, Hotel, Zeltplatz …"></textarea>
                <small class="wr-help">Wo kannst du kurzfristig erreicht werden, falls wir dich kontaktieren müssen?</small>
            </div>
            <div class="field required">
                <label for="guest-kontakt-telefon">Kontaktrufnummer (Notfallkontakt)</label>
                <input type="tel" id="guest-kontakt-telefon" name="kontakt_telefon" maxlength="100" required
                       value="{{ old('kontakt_telefon') }}" placeholder="z. B. +49 123 456789">
                <small class="wr-help">Diese Nummer wird im Notfall kontaktiert.</small>
            </div>
        </fieldset>

        @if ($adventure->fee_reduced !== null && $adventure->fee > 0)
            <fieldset class="wr-fieldset">
                <legend>Beitrag</legend>
                <div class="field">
                    <label class="wr-check">
                        <input type="checkbox" name="ermaessigung" value="1">
                        <span class="wr-check__text">
                            <span class="wr-check__title">
                                Ermäßigung beantragen
                                <span class="font-normal">({{ number_format($adventure->fee_reduced, 2, ',', '.') }} € statt {{ number_format($adventure->fee, 2, ',', '.') }} €)</span>
                            </span>
                            <span class="wr-check__hint">
                                Die Ermäßigung muss beim Check-in nachgewiesen werden, z.&nbsp;B. durch einen Bescheid über
                                Grundsicherung (SGB II/XII), Kinderzuschlag oder Wohngeld. Ohne Nachweis gilt der reguläre Beitrag.
                            </span>
                        </span>
                    </label>
                </div>
            </fieldset>
        @endif

        <fieldset class="wr-fieldset">
            <legend>Zustimmung</legend>
            <div class="field required">
                <div class="wr-check">
                    <input type="checkbox" id="guest-agb" name="agb" value="1" required>
                    <span class="wr-check__text">
                        <label for="guest-agb" class="wr-check__title cursor-pointer" style="display:inline; font-size:1rem; margin:0;">Ich stimme den</label>
                        <a href="{{ route('agb') }}" target="_blank" rel="noopener" class="wr-interactive underline text-wr-forest font-semibold">Teilnahmebedingungen</a>
                        <label for="guest-agb" class="wr-check__title cursor-pointer" style="display:inline; font-size:1rem; margin:0;">zu.</label>
                        <span class="wr-check__hint">Öffnet in einem neuen Fenster.</span>
                    </span>
                </div>
            </div>

            {{-- Zusätzliche Pflicht-Bestätigungen des Auftraggebers (Issue #14) --}}
            @include('bookings._requirements', ['idPrefix' => 'guest_req'])
        </fieldset>
    </form>

    <div data-modal-actions hidden>
        <button type="submit" form="booking-guest-form" class="wr-btn wr-btn--primary">Gast anmelden</button>
    </div>
@endif
</div>
