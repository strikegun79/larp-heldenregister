<span data-modal-title hidden>{{ $location->exists ? 'Ort bearbeiten: '.$location->titel : 'Neuer Ort' }}</span>

<form id="location-form" method="POST"
      action="{{ $location->exists ? route('admin.locations.update', $location) : route('admin.locations.store') }}"
      class="ui form space-y-4">
    @csrf
    @if ($location->exists) @method('PUT') @endif

    <div class="field">
        <label>Titel</label>
        <input type="text" name="titel" value="{{ old('titel', $location->titel) }}" required>
        <x-input-error :messages="$errors->get('titel')" class="mt-2" />
    </div>

    <div class="two fields">
        <div class="field">
            <label>PLZ</label>
            <input type="text" name="plz" value="{{ old('plz', $location->plz) }}">
            <x-input-error :messages="$errors->get('plz')" class="mt-2" />
        </div>
        <div class="field">
            <label>Stadt</label>
            <input type="text" name="city" value="{{ old('city', $location->city) }}">
            <x-input-error :messages="$errors->get('city')" class="mt-2" />
        </div>
    </div>

    <div class="field">
        <label>Adresse</label>
        <input type="text" name="address" value="{{ old('address', $location->address) }}">
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>

    <div class="field">
        <label>GPS (z.&nbsp;B. „50.58, 8.67")</label>
        <input type="text" name="gps" value="{{ old('gps', $location->gps) }}">
        <x-input-error :messages="$errors->get('gps')" class="mt-2" />
    </div>

    <div class="field">
        <label>Bild (URL/Pfad)</label>
        <input type="text" name="image" value="{{ old('image', $location->image) }}">
        <x-input-error :messages="$errors->get('image')" class="mt-2" />
    </div>
</form>

<div data-modal-actions hidden>
    <button type="submit" form="location-form" class="ui primary button">Speichern</button>
</div>
