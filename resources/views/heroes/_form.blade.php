@csrf
{{-- Heldenformular (Waldritter v1) für Anlegen und Bearbeiten (nur Team). Die umgebende Ansicht
     stellt den .wr-v1-Wrapper bereit. Feldnamen bleiben (Validierung im HeroController).
     Die Spieler-Suche ist ein Fomantic-Dropdown: im Modal initialisiert loadModalContent es,
     auf der Vollseite das Skript unten. --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery) $('#player_id').dropdown();
});
</script>
@endpush

<div class="wr-form space-y-5">

    <fieldset class="wr-fieldset">
        <legend>Zuordnung</legend>
        <div class="mb-4">
            <x-input-label for="player_id" value="Spieler" class="wr-field-label" />
            <select id="player_id" name="player_id" class="ui fluid search selection dropdown mt-1">
                <option value="">— bitte wählen —</option>
                @foreach ($players as $player)
                    <option value="{{ $player->id }}" @selected(old('player_id', $hero->player_id) == $player->id)>
                        {{ $player->full_name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('player_id')" class="mt-2" />
            <small class="wr-help">Tippen, um nach Namen zu suchen.</small>
        </div>
    </fieldset>

    <fieldset class="wr-fieldset">
        <legend>Held</legend>
        <div class="grid gap-4 sm:grid-cols-2 mb-4">
            <div>
                <x-input-label for="character_name" value="Charaktername" class="wr-field-label" />
                <x-text-input id="character_name" name="character_name" type="text" class="mt-1 block w-full"
                              :value="old('character_name', $hero->character_name)" />
                <x-input-error :messages="$errors->get('character_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="homeplace" value="Heimatort" class="wr-field-label" />
                <x-text-input id="homeplace" name="homeplace" type="text" class="mt-1 block w-full"
                              :value="old('homeplace', $hero->homeplace)" />
                <x-input-error :messages="$errors->get('homeplace')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                {{-- Steckbrief: Hintergrund-Freitext (HERO-09) --}}
                <x-input-label for="description" value="Steckbrief / Hintergrund" class="wr-field-label" />
                <div class="mt-1">
                    @include('heroes._steckbrief_editor', ['id' => 'description', 'value' => old('description', $hero->description_html)])
                </div>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
        </div>
    </fieldset>

    <fieldset class="wr-fieldset">
        <legend>Daten</legend>
        <div class="grid gap-4 sm:grid-cols-2 mb-4">
            <div>
                <x-input-label for="born" value="Erste Erblickung" class="wr-field-label" />
                <x-date-picker name="born" :value="old('born', optional($hero->born)->format('Y-m-d'))" />
                <small class="wr-help">Das Datum, an dem der Held zum ersten Mal in der Spielwelt aufgetreten ist (Geburtstag des Charakters).</small>
                <x-input-error :messages="$errors->get('born')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="died" value="Verschollen" class="wr-field-label" />
                <x-date-picker name="died" :value="old('died', optional($hero->died)->format('Y-m-d'))" />
                <small class="wr-help">Ab wann der Held nicht mehr aktiv spielt. Leer lassen, wenn der Held noch aktiv ist.</small>
                <x-input-error :messages="$errors->get('died')" class="mt-2" />
            </div>
        </div>
    </fieldset>

    {{-- Startklassen nur bei der Neuanlage (kostenfrei). Spätere Klassen kosten EP
         und werden im Helden-Detail über die Klassenverwaltung hinzugefügt (HERO-06). --}}
    @unless ($hero->exists)
        <fieldset class="wr-fieldset">
            <legend>Startklassen</legend>
            <p class="wr-fieldset__intro">Bei der Neuanlage kostenfrei. Weitere Klassen kosten später EP.</p>
            <div class="grid gap-2 sm:grid-cols-2 mb-3">
                @foreach ($classes as $class)
                    <label class="wr-check">
                        <input type="checkbox" name="classes[]" value="{{ $class->id }}"
                               @checked(in_array($class->id, old('classes', $hero->classes->pluck('id')->all())))>
                        <span class="wr-check__text"><span class="wr-check__title">{{ $class->name }}</span></span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('classes')" class="mb-2" />
        </fieldset>
    @endunless

    <fieldset class="wr-fieldset">
        <legend>Freigabe und Sichtbarkeit</legend>
        <div class="grid gap-2 sm:grid-cols-2 mb-3">
            @if ($hero->exists)
                <label class="wr-check">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $hero->active))>
                    <span class="wr-check__text">
                        <span class="wr-check__title">Freigegeben</span>
                        <span class="wr-check__hint">Der Held ist im Register aktiv.</span>
                    </span>
                </label>
            @endif
            <label class="wr-check">
                <input type="checkbox" name="public_visible" value="1" @checked(old('public_visible', $hero->public_visible ?? true))>
                <span class="wr-check__text">
                    <span class="wr-check__title">Öffentlich sichtbar</span>
                    <span class="wr-check__hint">Profil ist über das Helden-Siegel (/h/{code}) ohne Anmeldung abrufbar.</span>
                </span>
            </label>
        </div>
    </fieldset>

    @unless ($hideFormActions ?? false)
        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="wr-btn wr-btn--primary">Speichern</button>
            <a href="{{ route('heroes.index') }}" class="wr-btn wr-btn--secondary">Abbrechen</a>
        </div>
    @endunless
</div>
