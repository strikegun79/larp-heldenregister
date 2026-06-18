<span data-modal-title hidden>{{ $group->exists ? 'Gruppe bearbeiten: '.$group->name : 'Neue Gruppe' }}</span>

<form id="group-form" method="POST"
      action="{{ $group->exists ? route('admin.groups.update', $group) : route('admin.groups.store') }}"
      class="ui form space-y-4">
    @csrf
    @if ($group->exists) @method('PUT') @endif

    <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name', $group->name) }}" required maxlength="100">
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="field">
        <label>Beschreibung</label>
        <textarea name="description" rows="3" maxlength="1000">{{ old('description', $group->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>
</form>

<div data-modal-actions hidden>
    <button type="submit" form="group-form" class="ui primary button">Speichern</button>
</div>
