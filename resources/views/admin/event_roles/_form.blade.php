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
</form>

<div data-modal-actions hidden>
    <button type="submit" form="role-form" class="ui primary button">Speichern</button>
</div>
