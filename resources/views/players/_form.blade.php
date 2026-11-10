@csrf
@php($selectClass = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-amber-600 focus:ring-amber-600')

{{-- UI-32: Datenschutzhinweis --}}
<div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 mb-5 text-sm text-blue-800">
    <strong>Datenschutzhinweis:</strong> Die hier erfassten Angaben werden ausschließlich für die Organisation der Waldritter-Veranstaltungen verwendet und sind nur für das Organisationsteam sichtbar. Sie werden nicht an Dritte weitergegeben. Fragen zum Datenschutz beantwortet dir das Organisationsteam.
</div>

<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="name" value="Vorname" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name', $player->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="lastname" value="Nachname" />
            <x-text-input id="lastname" name="lastname" type="text" class="mt-1 block w-full"
                          :value="old('lastname', $player->lastname)" required />
            <x-input-error :messages="$errors->get('lastname')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="dayofbirth" value="Geburtsdatum" />
            <x-date-picker name="dayofbirth"
                           :value="old('dayofbirth', optional($player->dayofbirth)->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('dayofbirth')" class="mt-2" />
            <small class="text-stone-400">Wird für altersgerechte Gruppenaufteilung und die Notfallvorsorge benötigt.</small>
        </div>
        <div>
            <x-input-label for="gender" value="Geschlecht" />
            <select id="gender" name="gender" class="{{ $selectClass }}">
                <option value="">— bitte wählen —</option>
                @foreach (['weiblich' => 'Weiblich', 'männlich' => 'Männlich', 'divers' => 'Divers'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('gender', $player->gender) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="email" value="E-Mail (optional)" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                      :value="old('email', $player->email)" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
        <small class="text-stone-400">Optional – nur für das Organisationsteam sichtbar, z. B. für Rückfragen zur Anmeldung.</small>
    </div>

    {{-- Kinder-Anschrift (PLAY-14 / ORGA-01) --}}
    <div>
        <label class="flex items-center gap-2 text-stone-700 font-medium">
            <input type="checkbox" id="address_same_as_guardian_toggle"
                   class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-600"
                   @checked(old('address_same_as_guardian', $player->address_same_as_guardian ?? true))
                   onchange="
                       document.getElementById('child-address-fields').classList.toggle('hidden', this.checked);
                       document.querySelector('[name=address_same_as_guardian]').value = this.checked ? '1' : '0';
                   ">
            Anschrift entspricht der Anschrift der erziehungsberechtigten Person
        </label>
        <input type="hidden" name="address_same_as_guardian"
               value="{{ old('address_same_as_guardian', ($player->address_same_as_guardian ?? true) ? '1' : '0') }}">
    </div>

    <div id="child-address-fields"
         class="{{ old('address_same_as_guardian', ($player->address_same_as_guardian ?? true) ? '1' : '0') === '1' ? 'hidden' : '' }} space-y-4">
        <p class="text-sm text-stone-500">Abweichende Anschrift des Kindes</p>
        <small class="text-stone-400 block -mt-2">Nur für administrative Zwecke und postalische Kommunikation des Veranstalters – nicht öffentlich sichtbar.</small>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="street" value="Straße" />
                <x-text-input id="street" name="street" type="text" class="mt-1 block w-full"
                              :value="old('street', $player->street)" maxlength="100" />
                <x-input-error :messages="$errors->get('street')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="house_number" value="Hausnummer" />
                <x-text-input id="house_number" name="house_number" type="text" class="mt-1 block w-full"
                              :value="old('house_number', $player->house_number)" maxlength="10" />
                <x-input-error :messages="$errors->get('house_number')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="zip" value="PLZ" />
                <x-text-input id="zip" name="zip" type="text" class="mt-1 block w-full"
                              :value="old('zip', $player->zip)" maxlength="10" />
                <x-input-error :messages="$errors->get('zip')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="city" value="Ort" />
                <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                              :value="old('city', $player->city)" maxlength="100" />
                <x-input-error :messages="$errors->get('city')" class="mt-2" />
            </div>
        </div>
    </div>

    {{-- DSGVO Art. 9: Gesundheitsdaten am Spielerprofil (PLAY-15 / H-1) --}}
    <fieldset class="border border-amber-200 rounded-lg p-4 bg-amber-50">
        <legend class="text-sm font-semibold text-amber-800 px-1">Gesundheitsdaten (optional)</legend>

        <div class="space-y-4">
            <div>
                <x-input-label for="allergien" value="Allergien / Unverträglichkeiten" />
                <textarea id="allergien" name="allergien" rows="2"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-amber-600 focus:ring-amber-600 text-sm"
                          placeholder="z. B. Nüsse, Laktose, Bienen …"
                          oninput="updateHealthConsent()">{{ old('allergien', $player->allergien) }}</textarea>
                <x-input-error :messages="$errors->get('allergien')" class="mt-2" />
                <small class="text-stone-400">Wird bei einer Abenteuer-Anmeldung automatisch vorausgefüllt.</small>
            </div>

            <div>
                <x-input-label for="medikamente" value="Medikamente" />
                <textarea id="medikamente" name="medikamente" rows="2"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-amber-600 focus:ring-amber-600 text-sm"
                          placeholder="z. B. Epipen, Inhalator, tägliche Einnahme …"
                          oninput="updateHealthConsent()">{{ old('medikamente', $player->medikamente) }}</textarea>
                <x-input-error :messages="$errors->get('medikamente')" class="mt-2" />
                <small class="text-stone-400">Regelmäßige Medikamente, die dein Kind während der Veranstaltung benötigt.</small>
            </div>

            {{-- Einwilligung: erscheint nur wenn Felder befüllt (JS), Pflicht dann auch per Server --}}
            <div id="health-consent-block"
                 class="{{ (old('allergien', $player->allergien) || old('medikamente', $player->medikamente)) ? '' : 'hidden' }}
                         bg-blue-50 border border-blue-200 rounded p-3 text-xs text-blue-800">
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" id="health_data_consent" name="health_data_consent" value="1"
                           class="mt-0.5 rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-600"
                           @checked(old('health_data_consent', $player->health_data_consent_at ? '1' : ''))>
                    <span>
                        <strong>Einwilligung (Art. 9 Abs. 2 lit. a DSGVO):</strong>
                        Ich willige ausdrücklich ein, dass die oben angegebenen Gesundheitsdaten
                        (Allergien, Medikamente) im Spielerprofil gespeichert werden.
                        Diese Daten werden ausschließlich zum Schutz meines Kindes in Notfallsituationen
                        während der Veranstaltungen verwendet und sind nur für das Organisationsteam sichtbar.
                        Die Einwilligung kann jederzeit widerrufen werden (Felder leeren und speichern).
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
    document.addEventListener('DOMContentLoaded', updateHealthConsent);
    </script>

    {{-- DSGVO Art. 8: Einwilligung Erziehungsberechtigte für Minderjährige (H-5) --}}
    <div id="parental-consent-block"
         class="{{ old('self', $self ?? false) ? 'hidden' : '' }}
                 border border-green-300 rounded-lg p-4 bg-green-50">
        <p class="text-sm font-semibold text-green-800 mb-2">
            <i class="shield alternate icon"></i> Einwilligung Erziehungsberechtigte (Art. 8 DSGVO)
        </p>
        <label class="flex items-start gap-2 cursor-pointer text-sm text-green-900">
            <input type="checkbox" id="parental_consent" name="parental_consent" value="1"
                   class="mt-0.5 rounded border-gray-300 text-green-700 shadow-sm focus:ring-green-600"
                   @checked(old('parental_consent', $player->parental_consent_at ? '1' : ''))>
            <span>
                Ich bestätige, dass ich als Erziehungsberechtigte/r berechtigt bin, für dieses Kind
                personenbezogene Daten im Heldenregister zu verwalten, und willige in die Speicherung
                der Profildaten zum Zweck der Veranstaltungsorganisation ein
                (Art. 6 Abs. 1 lit. b und Art. 8 DSGVO).
                @if ($player->parental_consent_at)
                    <span class="text-green-700 font-medium">(Einwilligung erteilt am {{ $player->parental_consent_at->format('d.m.Y H:i') }})</span>
                @endif
            </span>
        </label>
        <x-input-error :messages="$errors->get('parental_consent')" class="mt-2" />
    </div>

    <label class="flex items-center gap-2 text-stone-700">
        <input type="checkbox" id="self_checkbox" name="self" value="1"
               class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-600"
               @checked(old('self', $self ?? false))
               onchange="toggleParentalConsent(this.checked)">
        Das bin ich selbst (eigener Spieler)
    </label>

    <script>
    function toggleParentalConsent(isSelf) {
        const block = document.getElementById('parental-consent-block');
        const cb    = document.getElementById('parental_consent');
        if (!block || !cb) return;
        block.classList.toggle('hidden', isSelf);
        cb.required = !isSelf;
        if (isSelf) cb.checked = false;
    }
    document.addEventListener('DOMContentLoaded', function () {
        const selfCb = document.getElementById('self_checkbox');
        if (selfCb) toggleParentalConsent(selfCb.checked);
    });
    </script>
</div>
