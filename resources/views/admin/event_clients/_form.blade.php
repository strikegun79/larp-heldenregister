<span data-modal-title hidden>{{ $client->exists ? 'Auftraggeber bearbeiten: '.$client->name : 'Neuer Auftraggeber' }}</span>

<form id="client-form" method="POST"
      action="{{ $client->exists ? route('admin.event-clients.update', $client) : route('admin.event-clients.store') }}"
      class="ui form space-y-4">
    @csrf
    @if ($client->exists) @method('PUT') @endif

    <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name', $client->name) }}" required>
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
</form>

<div data-modal-actions hidden>
    <button type="submit" form="client-form" class="ui primary button">Speichern</button>
</div>
