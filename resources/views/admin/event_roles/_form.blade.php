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
</form>

<div data-modal-actions hidden>
    <button type="submit" form="role-form" class="ui primary button">Speichern</button>
</div>
