@csrf
@php($selectClass = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500')
<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="name" value="Vorname" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name', $player->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="lastname" value="Nachname" />
            <x-text-input id="lastname" name="lastname" type="text" class="mt-1 block w-full"
                          :value="old('lastname', $player->lastname)" required />
            <x-input-error :messages="$errors->get('lastname')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="dayofbirth" value="Geburtsdatum" />
            <x-text-input id="dayofbirth" name="dayofbirth" type="date" class="mt-1 block w-full"
                          :value="old('dayofbirth', optional($player->dayofbirth)->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('dayofbirth')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="gender" value="Geschlecht" />
            <select id="gender" name="gender" class="{{ $selectClass }}">
                <option value="">— bitte wählen —</option>
                @foreach (['weiblich' => 'Weiblich', 'männlich' => 'Männlich', 'divers' => 'Divers'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('gender', $player->gender) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="email" value="E-Mail (optional)" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                      :value="old('email', $player->email)" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    {{-- Kinder-Anschrift (PLAY-14 / ORGA-01) --}}
    <div>
        <label class="flex items-center gap-2 text-stone-700 font-medium">
            <input type="checkbox" id="address_same_as_guardian_toggle"
                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                   @checked(old('address_same_as_guardian', $player->address_same_as_guardian ?? true))
                   onchange="
                       document.getElementById('child-address-fields').classList.toggle('hidden', this.checked);
                       document.querySelector('[name=address_same_as_guardian]').value = this.checked ? '1' : '0';
                   ">
            Anschrift entspricht der Anschrift der erziehungsberechtigten Person
        </label>
        <input type="hidden" name="address_same_as_guardian"
               value="{{ old('address_same_as_guardian', ($player->address_same_as_guardian ?? true) ? '1' : '0') }}">
    </div>

    <div id="child-address-fields"
         class="{{ old('address_same_as_guardian', ($player->address_same_as_guardian ?? true) ? '1' : '0') === '1' ? 'hidden' : '' }} space-y-4">
        <p class="text-sm text-stone-500">Abweichende Anschrift des Kindes</p>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="street" value="Straße" />
                <x-text-input id="street" name="street" type="text" class="mt-1 block w-full"
                              :value="old('street', $player->street)" maxlength="100" />
                <x-input-error :messages="$errors->get('street')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="house_number" value="Hausnummer" />
                <x-text-input id="house_number" name="house_number" type="text" class="mt-1 block w-full"
                              :value="old('house_number', $player->house_number)" maxlength="10" />
                <x-input-error :messages="$errors->get('house_number')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="zip" value="PLZ" />
                <x-text-input id="zip" name="zip" type="text" class="mt-1 block w-full"
                              :value="old('zip', $player->zip)" maxlength="10" />
                <x-input-error :messages="$errors->get('zip')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="city" value="Ort" />
                <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                              :value="old('city', $player->city)" maxlength="100" />
                <x-input-error :messages="$errors->get('city')" class="mt-2" />
            </div>
        </div>
    </div>

    <label class="flex items-center gap-2 text-stone-700">
        <input type="checkbox" name="self" value="1"
               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
               @checked(old('self', $self ?? false))>
        Das bin ich selbst (eigener Spieler)
    </label>
</div>
