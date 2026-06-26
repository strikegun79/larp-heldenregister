@csrf
<div class="space-y-6">
    <div>
        <x-input-label for="player_id" value="Spieler" />
        <select id="player_id" name="player_id"
                class="mt-1 block w-full border-gray-300 focus:border-amber-600 focus:ring-amber-600 rounded-md shadow-sm">
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

    {{-- Steckbrief: Hintergrund-Freitext + Avatar-Bild (HERO-09). --}}
    <div>
        <x-input-label for="description" value="Steckbrief / Hintergrund" />
        <textarea id="description" name="description" rows="4"
                  class="mt-1 block w-full border-gray-300 focus:border-amber-600 focus:ring-amber-600 rounded-md shadow-sm">{{ old('description', $hero->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="image" value="Bild (JPG/PNG/WebP, max. 4 MB)" />
        @if ($hero->image_url)
            <img src="{{ $hero->image_url }}" alt="Avatar" class="mb-2 h-24 w-24 object-cover rounded border">
        @endif
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm">
        <x-input-error :messages="$errors->get('image')" class="mt-2" />
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="born" value="Erste Erblickung" />
            <x-date-picker name="born"
                           :value="old('born', optional($hero->born)->format('Y-m-d'))" />
            <p class="mt-1 text-xs text-gray-500">Das Datum, an dem der Held zum ersten Mal in der Spielwelt aufgetreten ist (Geburtstag des Charakters).</p>
            <x-input-error :messages="$errors->get('born')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="died" value="Verschollen" />
            <x-date-picker name="died"
                           :value="old('died', optional($hero->died)->format('Y-m-d'))" />
            <p class="mt-1 text-xs text-gray-500">Das Datum, ab dem der Held nicht mehr aktiv spielt (z. B. in Rente gegangen oder aus der Spielwelt verschwunden). Leer lassen, wenn der Held noch aktiv ist.</p>
            <x-input-error :messages="$errors->get('died')" class="mt-2" />
        </div>
    </div>

    {{-- Startklassen nur bei der Neuanlage (kostenfrei). Spätere Klassen kosten EP
         und werden im Helden-Detail über die Klassenverwaltung hinzugefügt (HERO-06). --}}
    @unless ($hero->exists)
        <div>
            <x-input-label value="Startklassen" />
            <div class="mt-1 grid grid-cols-2 gap-2">
                @foreach ($classes as $class)
                    <label class="flex items-center gap-2 text-gray-700">
                        <input type="checkbox" name="classes[]" value="{{ $class->id }}"
                               class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-600"
                               @checked(in_array($class->id, old('classes', $hero->classes->pluck('id')->all())))>
                        {{ $class->name }}
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('classes')" class="mt-2" />
        </div>
    @endunless

    <div class="space-y-2">
        <label class="flex items-center gap-2 text-gray-700">
            <input type="checkbox" name="active" value="1"
                   class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-600"
                   @checked(old('active', $hero->active))>
            Aktiver Held
        </label>
        <label class="flex items-center gap-2 text-gray-700">
            <input type="checkbox" name="public_visible" value="1"
                   class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-600"
                   @checked(old('public_visible', $hero->public_visible ?? true))>
            Öffentlich sichtbar
            <span class="text-xs text-gray-400">(Profil unter /h/{code} abrufbar)</span>
        </label>
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>Speichern</x-primary-button>
        <a href="{{ route('heroes.index') }}"
           class="text-sm text-gray-600 hover:underline">Abbrechen</a>
    </div>
</div>
