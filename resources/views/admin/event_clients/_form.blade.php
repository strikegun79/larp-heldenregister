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

    <div class="field">
        <label>Kürzel (Verwendungszweck)</label>
        <input type="text" name="kuerzel" value="{{ old('kuerzel', $client->kuerzel) }}" maxlength="20" placeholder="z. B. WR, JFG">
        <small class="text-stone-400">Wird im Verwendungszweck der Überweisungsbestätigung vorangestellt: Kürzel + Datum + Teilnehmername.</small>
        <x-input-error :messages="$errors->get('kuerzel')" class="mt-2" />
    </div>
</form>

<div data-modal-actions hidden>
    <button type="submit" form="client-form" class="ui primary button">Speichern</button>
</div>
