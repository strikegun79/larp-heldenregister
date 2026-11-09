<span data-modal-title hidden>Anschrift: {{ $player->full_name }}</span>

<form id="player-address-form" method="POST"
      action="{{ route('admin.players.update', $player) }}"
      class="ui form space-y-4">
    @csrf
    @method('PUT')

    <p class="text-sm text-stone-600">
        Standardmäßig gilt die Anschrift der erziehungsberechtigten Person.
        Nur bei abweichender Kinder-Anschrift aktivieren.
    </p>

    <div class="field">
        <label class="flex items-center gap-2">
            <input type="hidden" name="address_same_as_guardian" value="1">
            <input type="checkbox" id="address_same_as_guardian" name="address_same_as_guardian" value="0"
                   @checked(! $player->address_same_as_guardian)
                   onchange="document.getElementById('child-address').classList.toggle('hidden', !this.checked)">
            Abweichende Anschrift des Kindes
        </label>
    </div>

    <div id="child-address" class="{{ $player->address_same_as_guardian ? 'hidden' : '' }} space-y-3">
        <div class="grid grid-cols-2 gap-3">
            <div class="field">
                <label>Straße</label>
                <input type="text" name="street" value="{{ old('street', $player->street) }}" maxlength="100">
                <x-input-error :messages="$errors->get('street')" class="mt-1" />
            </div>
            <div class="field">
                <label>Hausnummer</label>
                <input type="text" name="house_number" value="{{ old('house_number', $player->house_number) }}" maxlength="10">
                <x-input-error :messages="$errors->get('house_number')" class="mt-1" />
            </div>
            <div class="field">
                <label>PLZ</label>
                <input type="text" name="zip" value="{{ old('zip', $player->zip) }}" maxlength="10">
                <x-input-error :messages="$errors->get('zip')" class="mt-1" />
            </div>
            <div class="field">
                <label>Ort</label>
                <input type="text" name="city" value="{{ old('city', $player->city) }}" maxlength="100">
                <x-input-error :messages="$errors->get('city')" class="mt-1" />
            </div>
        </div>
    </div>
</form>

<div data-modal-actions hidden>
    <button type="submit" form="player-address-form" class="ui primary button">Speichern</button>
</div>
