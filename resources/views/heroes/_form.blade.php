@csrf
<div class="space-y-6">
    <div>
        <x-input-label for="player_id" value="Spieler" />
        <select id="player_id" name="player_id"
                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">— bitte wählen —</option>
            @foreach ($players as $player)
                <option value="{{ $player->id }}"
                    @selected(old('player_id', $hero->player_id) == $player->id)>
                    {{ $player->full_name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('player_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="character_name" value="Charaktername" />
        <x-text-input id="character_name" name="character_name" type="text" class="mt-1 block w-full"
                      :value="old('character_name', $hero->character_name)" />
        <x-input-error :messages="$errors->get('character_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="homeplace" value="Heimatort" />
        <x-text-input id="homeplace" name="homeplace" type="text" class="mt-1 block w-full"
                      :value="old('homeplace', $hero->homeplace)" />
        <x-input-error :messages="$errors->get('homeplace')" class="mt-2" />
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="born" value="Erste Erblickung" />
            <x-text-input id="born" name="born" type="date" class="mt-1 block w-full"
                          :value="old('born', optional($hero->born)->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('born')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="died" value="Verschollen" />
            <x-text-input id="died" name="died" type="date" class="mt-1 block w-full"
                          :value="old('died', optional($hero->died)->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('died')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label value="Klassen" />
        <div class="mt-1 grid grid-cols-2 gap-2">
            @foreach ($classes as $class)
                <label class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="classes[]" value="{{ $class->id }}"
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                           @checked(in_array($class->id, old('classes', $hero->classes->pluck('id')->all())))>
                    {{ $class->name }}
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('classes')" class="mt-2" />
    </div>

    <div>
        <label class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="active" value="1"
                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                   @checked(old('active', $hero->active))>
            Aktiver Held
        </label>
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>Speichern</x-primary-button>
        <a href="{{ route('heroes.index') }}"
           class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Abbrechen</a>
    </div>
</div>
