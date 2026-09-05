<span data-modal-title hidden>Teamer-Anmeldung · {{ $adventure->name }}</span>

<form id="teamer-signup-form" data-stack-close method="POST" action="{{ route('adventures.teamer.store', $adventure) }}" class="ui form">
    @csrf

    <div class="field">
        <label>Kontaktrufnummer</label>
        <input type="text" name="kontakt_telefon" maxlength="50" value="{{ auth()->user()->phone ?? '' }}">
    </div>

    <div class="field">
        <label>Allergien</label>
        <textarea name="allergien" rows="2" placeholder="Lebensmittelallergien, Tierhaarallergien …"
                  oninput="updateTeamerHealthConsent()"></textarea>
    </div>

    <div class="field">
        <label>Medikamente</label>
        <textarea name="medikamente" rows="2" placeholder="Dauermedikation, Notfallmedikamente …"
                  oninput="updateTeamerHealthConsent()"></textarea>
    </div>

    {{-- DSGVO Art. 9: Einwilligung – erscheint per JS wenn Felder befüllt (H-2) --}}
    <div id="teamer-health-consent-block" class="hidden bg-blue-50 border border-blue-200 rounded p-3 text-xs text-blue-800">
        <label class="flex items-start gap-2 cursor-pointer">
            <input type="checkbox" id="teamer_health_data_consent" name="health_data_consent" value="1"
                   class="mt-0.5 rounded border-gray-300">
            <span>
                <strong>Einwilligung (Art. 9 Abs. 2 lit. a DSGVO):</strong>
                Ich willige ausdrücklich ein, dass die oben angegebenen Gesundheitsdaten
                (Allergien, Medikamente) für diese Veranstaltungsanmeldung gespeichert werden.
                Sie werden ausschließlich zur Notfallvorsorge verwendet und sind nur für die
                Veranstaltungsleitung sichtbar. Die Einwilligung kann durch Leeren der Felder widerrufen werden.
            </span>
        </label>
        @error('health_data_consent')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <script>
    function updateTeamerHealthConsent() {
        const a = document.querySelector('[name=allergien]')?.value.trim() ?? '';
        const m = document.querySelector('[name=medikamente]')?.value.trim() ?? '';
        const block = document.getElementById('teamer-health-consent-block');
        const cb    = document.getElementById('teamer_health_data_consent');
        if (!block || !cb) return;
        const has = a !== '' || m !== '';
        block.classList.toggle('hidden', !has);
        cb.required = has;
        if (!has) cb.checked = false;
    }
    document.addEventListener('DOMContentLoaded', updateTeamerHealthConsent);
    </script>

    <div class="my-3 space-y-2">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="leih_tunika" value="1"> Leih-Tunika benötigt
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="leih_waffe" value="1"> Leih-Waffe benötigt
        </label>
    </div>

    <div class="field">
        <label>Anmerkung</label>
        <textarea name="anmerkung" rows="2"></textarea>
    </div>

    <div class="field required">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="agb" value="1" required>
            Ich stimme der Hausordnung und den Teilnahmebedingungen zu.
        </label>
    </div>

</form>

<div data-modal-actions hidden>
    <button type="submit" form="teamer-signup-form" class="ui primary button">Als Teamer anmelden</button>
</div>
