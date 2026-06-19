<span data-modal-title hidden>Teamer-Anmeldung · {{ $adventure->name }}</span>

<form id="teamer-signup-form" data-stack-close method="POST" action="{{ route('adventures.teamer.store', $adventure) }}" class="ui form">
    @csrf

    <div class="field">
        <label>Kontaktrufnummer</label>
        <input type="text" name="kontakt_telefon" maxlength="50" value="{{ auth()->user()->phone ?? '' }}">
    </div>

    <div class="field">
        <label>Allergien</label>
        <textarea name="allergien" rows="2" placeholder="Lebensmittelallergien, Tierhaarallergien …"></textarea>
    </div>

    <div class="field">
        <label>Medikamente</label>
        <textarea name="medikamente" rows="2" placeholder="Dauermedikation, Notfallmedikamente …"></textarea>
    </div>

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
