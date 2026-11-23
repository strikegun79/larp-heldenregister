<span data-modal-title hidden>{{ $role->exists ? 'Rolle bearbeiten: '.$role->description : 'Neue Rolle' }}</span>

<form id="role-form" method="POST"
      action="{{ $role->exists ? route('admin.event-roles.update', $role) : route('admin.event-roles.store') }}"
      class="ui form space-y-4">
    @csrf
    @if ($role->exists) @method('PUT') @endif

    <div class="field">
        <label>Bezeichnung</label>
        <input type="text" name="description" value="{{ old('description', $role->description) }}" required>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="field">
        <label>Verwendung</label>
        <div class="ui checkbox">
            <input type="hidden" name="for_participant" value="0">
            <input type="checkbox" name="for_participant" id="for_participant" value="1"
                   {{ old('for_participant', $role->for_participant ?? true) ? 'checked' : '' }}>
            <label for="for_participant">Teilnehmer-Anmeldung</label>
        </div>
        <div class="ui checkbox mt-2">
            <input type="hidden" name="for_teamer" value="0">
            <input type="checkbox" name="for_teamer" id="for_teamer" value="1"
                   {{ old('for_teamer', $role->for_teamer ?? false) ? 'checked' : '' }}>
            <label for="for_teamer">Teamer-Anmeldung</label>
        </div>
    </div>

    {{-- Issue #19: Teamer-Abrechnung (Aufwandsentschädigung) --}}
    <div class="field">
        <label>Abrechnung</label>
        <div class="ui checkbox">
            <input type="hidden" name="billable" value="0">
            <input type="checkbox" name="billable" id="billable" value="1"
                   {{ old('billable', $role->billable ?? false) ? 'checked' : '' }}>
            <label>Abrechnungsfähig (Aufwandsentschädigung)</label>
        </div>
        <p class="text-sm text-stone-600 mt-1">
            Teamer mit dieser Rolle können eine ausgefüllte Abrechnung herunterladen, sofern die
            Veranstaltung für die Rolle eine Pauschale festlegt.
        </p>
    </div>

    <div class="field">
        <label for="default_compensation">Standard-Pauschale (€)</label>
        <input type="number" step="0.01" min="0" max="9999.99" name="default_compensation" id="default_compensation"
               value="{{ old('default_compensation', $role->default_compensation) }}" placeholder="z. B. 35">
        <p class="text-sm text-stone-600 mt-1">Vorschlag für neue Veranstaltungen, dort pro Veranstaltung änderbar.</p>
        <x-input-error :messages="$errors->get('default_compensation')" class="mt-2" />
    </div>
</form>

{{-- Löschen nur im Inspektor (?inspector=1), Rückfrage und Sperrgrund wie in der bisherigen Liste --}}
@if ($role->exists)
    @php($usedBy = $role->bookings()->count())
    <x-admin.inspector-delete :action="route('admin.event-roles.destroy', $role)"
        title="Rolle löschen" :confirm="'Rolle „'.$role->description.'“ löschen?'"
        :blocked="$usedBy ? 'Nicht löschbar: '.$usedBy.' Anmeldung(en) nutzen diese Rolle.' : null" />
@endif

<div data-modal-actions hidden>
    <button type="submit" form="role-form" class="ui primary button">Speichern</button>
</div>
