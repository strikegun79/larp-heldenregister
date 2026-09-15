<span data-modal-title hidden>Teamer bearbeiten · {{ $signup->user->name }} {{ $signup->user->lastname }}</span>

<form id="teamer-signup-edit-form" data-stack-close method="POST"
      action="{{ route('adventures.teamer.update', [$adventure, $signup]) }}" class="ui form">
    @csrf @method('PUT')

    <div class="two fields">
        <div class="field">
            <label>Teamer-Rolle</label>
            <select name="event_role_id">
                <option value="">— keine —</option>
                @foreach (\App\Models\EventRole::forTeamer()->orderBy('id')->get() as $role)
                    <option value="{{ $role->id }}" @selected($signup->event_role_id === $role->id)>{{ $role->description }}</option>
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
        <textarea name="allergien" rows="2"
                  oninput="updateTeamerEditConsent()">{{ $signup->allergien }}</textarea>
    </div>

    <div class="field">
        <label>Medikamente</label>
        <textarea name="medikamente" rows="2"
                  oninput="updateTeamerEditConsent()">{{ $signup->medikamente }}</textarea>
    </div>

    {{-- DSGVO Art. 9: Consent-Bestätigung beim Admin-Bearbeiten (H-2) --}}
    <div id="teamer-edit-consent-block"
         class="{{ ($signup->allergien || $signup->medikamente) ? '' : 'hidden' }}
                 bg-blue-50 border border-blue-200 rounded p-3 text-xs text-blue-800">
        <label class="flex items-start gap-2 cursor-pointer">
            <input type="checkbox" id="teamer_edit_health_data_consent" name="health_data_consent" value="1"
                   class="mt-0.5 rounded border-gray-300"
                   @checked($signup->health_data_consent_at)>
            <span>
                <strong>Einwilligung (Art. 9 DSGVO):</strong>
                Gesundheitsdaten werden mit Einwilligung des Teamers gespeichert.
                @if ($signup->health_data_consent_at)
                    <span class="text-green-700">(erteilt am {{ $signup->health_data_consent_at->format('d.m.Y H:i') }})</span>
                @else
                    <span class="text-amber-700">(noch nicht erteilt)</span>
                @endif
            </span>
        </label>
        @error('health_data_consent')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <script>
    function updateTeamerEditConsent() {
        const a = document.querySelector('[name=allergien]')?.value.trim() ?? '';
        const m = document.querySelector('[name=medikamente]')?.value.trim() ?? '';
        const block = document.getElementById('teamer-edit-consent-block');
        const cb    = document.getElementById('teamer_edit_health_data_consent');
        if (!block || !cb) return;
        const has = a !== '' || m !== '';
        block.classList.toggle('hidden', !has);
        cb.required = has;
        if (!has) cb.checked = false;
    }
    document.addEventListener('DOMContentLoaded', updateTeamerEditConsent);
    </script>

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
