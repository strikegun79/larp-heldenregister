<span data-modal-title hidden>Anmelden · {{ $adventure->name }}</span>

{{-- Abenteuer-Anmeldung (Waldritter v1). Lädt im gestapelten Modal; eigener .wr-v1-Wrapper.
     Feldnamen, IDs und .field-Struktur bleiben, weil heldenregister.js darauf zugreift
     (Gesundheits-Einwilligung, Vorbelegung aus dem Spielerprofil, Admin-Betreuer). --}}
<div class="wr-v1">

{{-- UI-37: Kontext-Strip auf Mobile (Header scrollt weg, Info bleibt) --}}
<div class="sm:hidden flex items-center gap-2 mb-3 text-xs text-wr-ink-muted">
    <span>Abenteuer-Detail</span>
    <span aria-hidden="true">›</span>
    <span class="font-semibold text-wr-ink">Anmeldung</span>
</div>

{{-- UI-31: Abenteuer-Kurzinfo vor dem Formular --}}
<div class="wr-card p-4 mb-4">
    <p class="wr-card__title text-lg mb-2">{{ $adventure->name }}</p>
    <x-wr.adventure-meta :adventure="$adventure" class="text-wr-ink-muted mb-2" />
    <div class="flex flex-wrap gap-1.5">
        <x-wr.adventure-status :adventure="$adventure" :show-slots="false" />
        @if ($adventure->max_player > 0)
            <span class="wr-pill"><i class="users icon" aria-hidden="true"></i>Noch {{ $adventure->freeSlots() }} von {{ $adventure->max_player }} frei</span>
        @endif
        @if ($adventure->fee > 0 && $adventure->fee_reduced !== null)
            <span class="wr-pill">ermäßigt {{ number_format($adventure->fee_reduced, 2, ',', '.') }} €</span>
        @endif
    </div>
</div>

@if (! $adventure->registrationOpen() && ! ($adminMode ?? false))
    <p class="text-wr-ink-muted">Die Anmeldung ist derzeit nicht geöffnet (Status: {{ $adventure->status?->description }}).</p>
@elseif ($players->isEmpty())
    <p class="text-wr-ink-muted">Alle wählbaren Spieler sind für dieses Abenteuer bereits angemeldet.</p>
@else
    @if ($adventure->isFull())
        <div class="wr-notice mb-4" role="note">
            <i class="hourglass half icon m-0 mt-0.5" aria-hidden="true"></i>
            <p>Das Abenteuer ist voll – neue Anmeldungen kommen auf die Warteliste.</p>
        </div>
    @endif

    @if ($adminMode ?? false)
        <div class="wr-notice mb-4" role="note">
            <i class="shield alternate icon m-0 mt-0.5" aria-hidden="true"></i>
            <p>
                <strong>Admin-Anmeldung:</strong> Alle Spieler sichtbar. Die Anmeldung ist unabhängig vom Anmeldestatus möglich,
                wird sofort bestätigt (auch bei vollem Abenteuer) und die Bestätigung geht an den gewählten Betreuer.
            </p>
        </div>
    @endif

    <form id="booking-create-form" data-stack-close method="POST" action="{{ route('adventures.bookings.store', $adventure) }}" class="ui form">
        @csrf
        @if ($adminMode ?? false)
            <input type="hidden" name="admin_mode" value="1">
        @endif

        <p class="wr-help mt-0 mb-4">Mit <span class="text-wr-danger font-bold" aria-hidden="true">*</span> markierte Felder sind Pflichtfelder.</p>

        {{-- 1. Wer nimmt teil? --}}
        <fieldset class="wr-fieldset">
            <legend>Wer nimmt teil?</legend>
            <div class="grid gap-x-4 sm:grid-cols-2">
                <div class="field required">
                    <label for="booking-player-select">Spieler</label>
                    <select name="player_id" id="booking-player-select" required
                            @if ($adminMode ?? false)
                                data-context-url="{{ route('adventures.bookings.player-context', [$adventure, '__ID__']) }}"
                            @else
                                data-prefill-url="{{ route('players.health-prefill', ['player' => '__ID__']) }}"
                            @endif
                            >
                        <option value="">— wählen —</option>
                        @foreach ($players as $player)
                            <option value="{{ $player->id }}">{{ $player->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field required">
                    <label for="booking-role-select">Rolle</label>
                    <select name="event_role_id" id="booking-role-select" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->description }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($adminMode ?? false)
                {{-- Issue #8: Anmeldung im Namen eines Betreuers des Spielers (nicht des Admins) --}}
                <div class="field required" id="booking-caretaker-field">
                    <label for="booking-caretaker-select">Betreuer / Erziehungsberechtigte</label>
                    <select name="caretaker_user_id" id="booking-caretaker-select" disabled>
                        <option value="">— zuerst Spieler wählen —</option>
                    </select>
                    <small class="wr-help" id="booking-caretaker-hint">
                        Erhält die Anmeldebestätigung und erscheint als Kontakt in der Teilnehmerliste.
                    </small>
                </div>
            @endif

            {{-- Der teilnehmende Held ist automatisch der aktive Held des Spielers (HERO-21). --}}
            <p class="wr-help mb-2">Es spielt automatisch der aktive Held des Spielers mit.</p>
        </fieldset>

        {{-- 2. Gut zu wissen (optional) --}}
        <fieldset class="wr-fieldset">
            <legend>Gut zu wissen <span class="font-normal text-sm">(optional)</span></legend>
            <div class="grid gap-2 sm:grid-cols-2 mb-3">
                <label class="wr-check">
                    <input type="checkbox" name="vegetarier" value="1">
                    <span class="wr-check__text"><span class="wr-check__title">Vegetarisches Essen</span></span>
                </label>
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
                        <span class="wr-check__hint">Übernimmt eine Nebenrolle in der Geschichte, statt mit dem eigenen Helden zu spielen.</span>
                    </span>
                </label>
            </div>
        </fieldset>

        {{-- 3. Fotoerlaubnis (optional, jederzeit widerrufbar) --}}
        <fieldset class="wr-fieldset">
            <legend>Fotoerlaubnis <span class="font-normal text-sm">(optional)</span></legend>
            <label class="wr-check mb-3">
                <input type="checkbox" name="fotoerlaubnis" value="1">
                <span class="wr-check__text">
                    <span class="wr-check__title">Fotos erlaubt</span>
                    <span class="wr-check__hint">
                        Der Verein darf Fotos von dieser Veranstaltung verwenden, auf denen der Spieler zu sehen ist.
                        Du kannst die Erlaubnis jederzeit im Spielerprofil unter „Fotoerlaubnis“ widerrufen.
                    </span>
                </span>
            </label>
        </fieldset>

        {{-- 4. Gesundheit und Notfall --}}
        <fieldset class="wr-fieldset">
            <legend>Gesundheit und Notfall</legend>
            <p class="wr-fieldset__intro">Diese Angaben sieht nur das Organisationsteam. Sie dienen ausschließlich der Sicherheit.</p>

            <div class="field">
                <label for="booking-allergien">Allergien / Unverträglichkeiten <span class="font-normal">(optional)</span></label>
                <textarea id="booking-allergien" name="allergien" rows="2"
                          placeholder="z. B. Nüsse, Laktose, Bienen …"></textarea>
            </div>

            <div class="field">
                <label for="booking-medikamente">Medikamente <span class="font-normal">(optional)</span></label>
                <textarea id="booking-medikamente" name="medikamente" rows="2"
                          placeholder="z. B. Epipen, Inhalator, tägliche Einnahme …"></textarea>
                <small class="wr-help">Regelmäßige Medikamente, die während der Veranstaltung benötigt werden.</small>
            </div>

            {{-- DSGVO Art. 9: Einwilligung für Gesundheitsdaten (Pflicht, wenn Allergien/Medikamente ausgefüllt) --}}
            <div class="field" id="health-consent-field-create">
                <label class="wr-check">
                    <input type="checkbox" name="health_data_consent" id="health_data_consent" value="1">
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
                <label for="booking-erreichbarkeit">Erreichbarkeit während der Veranstaltung <span class="font-normal">(optional)</span></label>
                <textarea id="booking-erreichbarkeit" name="erreichbarkeit" rows="2" placeholder="z. B. Handy-Nummer vor Ort, Hotel, Zeltplatz …"></textarea>
                <small class="wr-help">Wo kannst du kurzfristig erreicht werden, falls wir dich kontaktieren müssen?</small>
            </div>

            <div class="field required">
                <label for="booking-kontakt-telefon">Kontaktrufnummer (Notfallkontakt)</label>
                <input type="tel" id="booking-kontakt-telefon" name="kontakt_telefon" maxlength="100" required
                       value="{{ old('kontakt_telefon', $userPhone ?? '') }}"
                       placeholder="z. B. +49 123 456789">
                @if ($adminMode ?? false)
                    <small class="wr-help" id="booking-phone-hint">Wird aus dem Profil des gewählten Betreuers übernommen, sofern hinterlegt.</small>
                @elseif ($userPhone)
                    <small class="wr-help">Aus deinem Profil übernommen – du kannst die Nummer für dieses Event ändern.</small>
                @else
                    <small class="wr-help">Diese Nummer wird im Notfall kontaktiert.</small>
                @endif
            </div>
        </fieldset>

        {{-- 5. Beitrag --}}
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

        {{-- 6. Zustimmung --}}
        <fieldset class="wr-fieldset">
            <legend>Zustimmung</legend>
            <div class="field required">
                <div class="wr-check">
                    <input type="checkbox" id="booking-agb" name="agb" value="1" required>
                    <span class="wr-check__text">
                        <label for="booking-agb" class="wr-check__title cursor-pointer" style="display:inline; font-size:1rem; margin:0;">Ich stimme den</label>
                        <a href="{{ route('agb') }}" target="_blank" rel="noopener" class="wr-interactive underline text-wr-forest font-semibold">Teilnahmebedingungen</a>
                        <label for="booking-agb" class="wr-check__title cursor-pointer" style="display:inline; font-size:1rem; margin:0;">zu.</label>
                        <span class="wr-check__hint">Öffnet in einem neuen Fenster.</span>
                    </span>
                </div>
            </div>

            {{-- Zusätzliche Pflicht-Bestätigungen des Auftraggebers (Issue #14) --}}
            @include('bookings._requirements', ['idPrefix' => 'bk_req'])
        </fieldset>
    </form>

    <div data-modal-actions hidden>
        <button type="submit" form="booking-create-form" class="wr-btn wr-btn--primary">Anmeldung absenden</button>
    </div>

@endif
</div>
