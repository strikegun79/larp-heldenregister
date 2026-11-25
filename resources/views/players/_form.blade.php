@csrf
{{-- Spielerformular (Waldritter v1) für Anlegen und Bearbeiten, Modal und Vollseite.
     Die umgebende Ansicht stellt den .wr-v1-Wrapper bereit. Feldnamen und IDs bleiben unverändert
     (Validierung im PlayerController, Gesundheits-Einwilligung per Skript). --}}
@php
    // Bearbeiten: $self kommt vom Controller (true/false) → immer vorausgewählt.
    // Anlegen: kein $self → bewusst keine Vorauswahl.
    $isSelf = old('self', isset($self) ? ($self ? '1' : '0') : null);
    $selfChosen = $isSelf !== null && $isSelf !== '';
    $selfOn = in_array((string) $isSelf, ['1', 'true', 'on'], true);
@endphp

<div class="wr-form space-y-5">

    {{-- UI-32: Datenschutzhinweis --}}
    <div class="wr-notice" role="note">
        <i class="lock icon m-0 mt-0.5" aria-hidden="true"></i>
        <p>
            <strong>Datenschutz:</strong> Die Angaben werden ausschließlich für die Organisation der Waldritter-Veranstaltungen
            verwendet, sind nur für das Organisationsteam sichtbar und werden nicht an Dritte weitergegeben.
        </p>
    </div>

    {{-- Wen legst du an? (steuert die Einwilligung der Erziehungsberechtigten) --}}
    <fieldset class="wr-fieldset">
        <legend>Wen legst du an?</legend>
        <div class="grid gap-2 sm:grid-cols-2 mb-3" role="radiogroup">
            <label class="wr-check">
                <input type="radio" id="self_yes" name="self" value="1" required
                       @checked($selfChosen && $selfOn) onchange="toggleParentalConsent(true)">
                <span class="wr-check__text">
                    <span class="wr-check__title">Mich selbst</span>
                    <span class="wr-check__hint">Du nimmst selbst an Veranstaltungen teil (eigener Spieler).</span>
                </span>
            </label>
            <label class="wr-check">
                <input type="radio" id="self_no" name="self" value="0" required
                       @checked($selfChosen && ! $selfOn) onchange="toggleParentalConsent(false)">
                <span class="wr-check__text">
                    <span class="wr-check__title">Mein Kind / eine betreute Person</span>
                    <span class="wr-check__hint">Du verwaltest die Daten als erziehungsberechtigte Person.</span>
                </span>
            </label>
        </div>
        <x-input-error :messages="$errors->get('self')" class="mb-2" />
    </fieldset>

    {{-- Person --}}
    <fieldset class="wr-fieldset">
        <legend>Person</legend>
        <div class="grid gap-4 sm:grid-cols-2 mb-4">
            <div>
                <x-input-label for="name" value="Vorname" class="wr-field-label" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              :value="old('name', $player->name)" required autocomplete="given-name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="lastname" value="Nachname" class="wr-field-label" />
                <x-text-input id="lastname" name="lastname" type="text" class="mt-1 block w-full"
                              :value="old('lastname', $player->lastname)" required autocomplete="family-name" />
                <x-input-error :messages="$errors->get('lastname')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="dayofbirth" value="Geburtsdatum" class="wr-field-label" />
                <x-date-picker name="dayofbirth"
                               :value="old('dayofbirth', optional($player->dayofbirth)->format('Y-m-d'))" />
                <x-input-error :messages="$errors->get('dayofbirth')" class="mt-2" />
                <small class="wr-help">Für altersgerechte Gruppen und die Notfallvorsorge.</small>
            </div>
            <div>
                <x-input-label for="gender" value="Geschlecht" class="wr-field-label" />
                <select id="gender" name="gender" class="mt-1 block w-full">
                    <option value="">— bitte wählen —</option>
                    @foreach (['weiblich' => 'Weiblich', 'männlich' => 'Männlich', 'divers' => 'Divers'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender', $player->gender) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('gender')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="email" value="E-Mail (optional)" class="wr-field-label" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                              :value="old('email', $player->email)" autocomplete="email" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                <small class="wr-help">Nur für das Organisationsteam sichtbar, z.B. Talk-Zugang.</small>
            </div>
        </div>
    </fieldset>

    {{-- Anschrift (PLAY-14 / ORGA-01) --}}
    <fieldset class="wr-fieldset">
        <legend>Anschrift</legend>
        <label class="wr-check mb-3">
            <input type="checkbox" id="address_same_as_guardian_toggle"
                   @checked(old('address_same_as_guardian', $player->address_same_as_guardian ?? true))
                   onchange="
                       document.getElementById('child-address-fields').classList.toggle('hidden', this.checked);
                       document.querySelector('[name=address_same_as_guardian]').value = this.checked ? '1' : '0';
                   ">
            <span class="wr-check__text">
                <span class="wr-check__title">Anschrift entspricht der Anschrift der erziehungsberechtigten Person</span>
                <span class="wr-check__hint">Haken entfernen, um eine abweichende Anschrift einzutragen.</span>
            </span>
        </label>
        <input type="hidden" name="address_same_as_guardian"
               value="{{ old('address_same_as_guardian', ($player->address_same_as_guardian ?? true) ? '1' : '0') }}">

        <div id="child-address-fields"
             class="{{ old('address_same_as_guardian', ($player->address_same_as_guardian ?? true) ? '1' : '0') === '1' ? 'hidden' : '' }} mb-4">
            <p class="wr-help mt-0 mb-3">Abweichende Anschrift – nur für administrative Zwecke und Post des Veranstalters, nicht öffentlich sichtbar.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="street" value="Straße" class="wr-field-label" />
                    <x-text-input id="street" name="street" type="text" class="mt-1 block w-full"
                                  :value="old('street', $player->street)" maxlength="100" autocomplete="address-line1" />
                    <x-input-error :messages="$errors->get('street')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="house_number" value="Hausnummer" class="wr-field-label" />
                    <x-text-input id="house_number" name="house_number" type="text" class="mt-1 block w-full"
                                  :value="old('house_number', $player->house_number)" maxlength="10" />
                    <x-input-error :messages="$errors->get('house_number')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="zip" value="PLZ" class="wr-field-label" />
                    <x-text-input id="zip" name="zip" type="text" class="mt-1 block w-full"
                                  :value="old('zip', $player->zip)" maxlength="10" autocomplete="postal-code" />
                    <x-input-error :messages="$errors->get('zip')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="city" value="Ort" class="wr-field-label" />
                    <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                                  :value="old('city', $player->city)" maxlength="100" autocomplete="address-level2" />
                    <x-input-error :messages="$errors->get('city')" class="mt-2" />
                </div>
            </div>
        </div>
    </fieldset>

    {{-- DSGVO Art. 9: Gesundheitsdaten am Spielerprofil (PLAY-15 / H-1) --}}
    <fieldset class="wr-fieldset">
        <legend>Gesundheitsdaten <span class="font-normal text-sm">(optional)</span></legend>
        <p class="wr-fieldset__intro">Wird bei einer Abenteuer-Anmeldung automatisch vorausgefüllt und ist nur für das Organisationsteam sichtbar.</p>

        <div class="space-y-4 mb-4">
            <div>
                <x-input-label for="allergien" value="Allergien / Unverträglichkeiten" class="wr-field-label" />
                <textarea id="allergien" name="allergien" rows="2" class="mt-1 block w-full"
                          placeholder="z. B. Nüsse, Laktose, Bienen …"
                          oninput="updateHealthConsent()">{{ old('allergien', $player->allergien) }}</textarea>
                <x-input-error :messages="$errors->get('allergien')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="medikamente" value="Medikamente" class="wr-field-label" />
                <textarea id="medikamente" name="medikamente" rows="2" class="mt-1 block w-full"
                          placeholder="z. B. Epipen, Inhalator, tägliche Einnahme …"
                          oninput="updateHealthConsent()">{{ old('medikamente', $player->medikamente) }}</textarea>
                <x-input-error :messages="$errors->get('medikamente')" class="mt-2" />
                <small class="wr-help">Regelmäßige Medikamente, die während der Veranstaltung benötigt werden.</small>
            </div>

            {{-- Einwilligung: erscheint nur wenn Felder befüllt (Skript), Pflicht dann auch per Server --}}
            <div id="health-consent-block"
                 class="{{ (old('allergien', $player->allergien) || old('medikamente', $player->medikamente)) ? '' : 'hidden' }}">
                <label class="wr-check">
                    <input type="checkbox" id="health_data_consent" name="health_data_consent" value="1"
                           @checked(old('health_data_consent', $player->health_data_consent_at ? '1' : ''))>
                    <span class="wr-check__text">
                        <span class="wr-check__title">Einwilligung Gesundheitsdaten (Art. 9 Abs. 2 lit. a DSGVO)</span>
                        <span class="wr-check__hint">
                            Ich willige ausdrücklich ein, dass die oben angegebenen Gesundheitsdaten (Allergien, Medikamente)
                            im Spielerprofil gespeichert werden. Sie werden ausschließlich zum Schutz in Notfallsituationen
                            während der Veranstaltungen verwendet und sind nur für das Organisationsteam sichtbar.
                            Die Einwilligung kann jederzeit widerrufen werden (Felder leeren und speichern).
                        </span>
                    </span>
                </label>
                <x-input-error :messages="$errors->get('health_data_consent')" class="mt-2" />
            </div>
        </div>
    </fieldset>

    <script>
    function updateHealthConsent() {
        const allergien   = document.getElementById('allergien')?.value.trim() ?? '';
        const medikamente = document.getElementById('medikamente')?.value.trim() ?? '';
        const block       = document.getElementById('health-consent-block');
        const checkbox    = document.getElementById('health_data_consent');
        if (!block || !checkbox) return;
        const hasData = allergien !== '' || medikamente !== '';
        block.classList.toggle('hidden', !hasData);
        checkbox.required = hasData;
        if (!hasData) checkbox.checked = false;
    }
    updateHealthConsent();
    </script>

    {{-- DSGVO Art. 8: Einwilligung Erziehungsberechtigte für Minderjährige (H-5) – nur bei „Mein Kind“ --}}
    <fieldset id="parental-consent-block" class="wr-fieldset {{ ($selfChosen && ! $selfOn) ? '' : 'hidden' }}">
        <legend>Einwilligung der erziehungsberechtigten Person</legend>
        <label class="wr-check mb-3">
            <input type="checkbox" id="parental_consent" name="parental_consent" value="1"
                   @checked(old('parental_consent', $player->parental_consent_at ? '1' : ''))>
            <span class="wr-check__text">
                <span class="wr-check__title">Ich bin erziehungsberechtigt und willige ein (Art. 8 DSGVO)</span>
                <span class="wr-check__hint">
                    Ich bestätige, dass ich als Erziehungsberechtigte/r berechtigt bin, für dieses Kind
                    personenbezogene Daten im Heldenregister zu verwalten, und willige in die Speicherung
                    der Profildaten zum Zweck der Veranstaltungsorganisation ein (Art. 6 Abs. 1 lit. b und Art. 8 DSGVO).
                    @if ($player->parental_consent_at)
                        <strong>Einwilligung erteilt am {{ $player->parental_consent_at->format('d.m.Y H:i') }}.</strong>
                    @endif
                </span>
            </span>
        </label>
        <x-input-error :messages="$errors->get('parental_consent')" class="mb-2" />
    </fieldset>

    <script>
    function toggleParentalConsent(isSelf) {
        const block = document.getElementById('parental-consent-block');
        const cb    = document.getElementById('parental_consent');
        if (!block || !cb) return;
        block.classList.toggle('hidden', isSelf);
        cb.required = !isSelf;
        if (isSelf) cb.checked = false;
    }
    (function () {
        // Sofort ausführen: im Modal ist DOMContentLoaded bereits vorbei.
        const yes = document.getElementById('self_yes');
        const no  = document.getElementById('self_no');
        if (yes && yes.checked) toggleParentalConsent(true);
        else if (no && no.checked) toggleParentalConsent(false);
    })();
    </script>
</div>
