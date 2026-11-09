<span data-modal-title hidden>Teamer bearbeiten · {{ $signup->user->name }} {{ $signup->user->lastname }}</span>

<form id="teamer-signup-edit-form" data-stack-close method="POST"
      action="{{ route('adventures.teamer.update', [$adventure, $signup]) }}" class="ui form">
    @csrf @method('PUT')

    <div class="two fields">
        <div class="field">
            <label>Teamer-Rolle</label>
            <select name="teamer_role">
                <option value="">— keine —</option>
                @foreach (\App\Models\TeamerSignup::ROLES as $role)
                    <option value="{{ $role }}" @selected($signup->teamer_role === $role)>{{ $role }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>Kontaktrufnummer</label>
            <input type="text" name="kontakt_telefon" maxlength="50" value="{{ $signup->kontakt_telefon }}">
        </div>
    </div>

    <div class="field">
        <label>Allergien</label>
        <textarea name="allergien" rows="2">{{ $signup->allergien }}</textarea>
    </div>

    <div class="field">
        <label>Medikamente</label>
        <textarea name="medikamente" rows="2">{{ $signup->medikamente }}</textarea>
    </div>

    <div class="my-3 space-y-2">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="leih_tunika" value="1" @checked($signup->leih_tunika)> Leih-Tunika benötigt
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="leih_waffe" value="1" @checked($signup->leih_waffe)> Leih-Waffe benötigt
        </label>
    </div>

    <div class="field">
        <label>Anmerkung</label>
        <textarea name="anmerkung" rows="2">{{ $signup->anmerkung }}</textarea>
    </div>
</form>

<div data-modal-actions hidden>
    <button type="submit" form="teamer-signup-edit-form" class="ui primary button">Speichern</button>
</div>
