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

    <label class="flex items-center gap-2 text-stone-700">
        <input type="checkbox" name="self" value="1"
               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
               @checked(old('self', $self ?? false))>
        Das bin ich selbst (eigener Spieler)
    </label>

    <div class="flex items-center gap-4">
        <x-primary-button>Speichern</x-primary-button>
        <a href="{{ route('players.index') }}" class="text-sm text-stone-600 hover:underline">Abbrechen</a>
    </div>
</div>
