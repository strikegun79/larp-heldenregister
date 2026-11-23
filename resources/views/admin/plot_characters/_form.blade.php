<span data-modal-title hidden>{{ $character->exists ? 'Plot-Charakter bearbeiten: '.$character->name : 'Neuer Plot-Charakter' }}</span>

<form id="plot-character-form" method="POST" enctype="multipart/form-data"
      action="{{ $character->exists ? route('admin.plot-characters.update', $character) : route('admin.plot-characters.store') }}"
      class="ui form space-y-4">
    @csrf
    @if ($character->exists) @method('PUT') @endif

    <div class="field">
        <label for="plot-name">Name</label>
        <input type="text" id="plot-name" name="name" value="{{ old('name', $character->name) }}" maxlength="100" required>
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="field">
        <label for="plot-title">Beiname / Titel <span class="text-stone-400 font-normal">(z.&nbsp;B. „Hexe des Nebelwalds“)</span></label>
        <input type="text" id="plot-title" name="title" value="{{ old('title', $character->title) }}" maxlength="150">
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="field">
        <label for="plot-description">Steckbrief <span class="text-stone-400 font-normal">(öffentlich sichtbar – für Kinder verständlich schreiben)</span></label>
        <textarea id="plot-description" name="description" rows="8" maxlength="5000">{{ old('description', $character->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="field">
        <label for="plot-image">Bild <span class="text-stone-400 font-normal">(JPG, PNG oder WebP, max. 8&nbsp;MB)</span></label>
        @if ($character->imageUrl())
            <div class="flex items-center gap-3 mb-2">
                <img src="{{ $character->imageUrl() }}" alt="Aktuelles Bild von {{ $character->name }}"
                     class="h-20 w-20 object-cover rounded border border-stone-200" loading="lazy">
                <label class="flex items-center gap-2 text-stone-700 font-normal">
                    <input type="checkbox" name="remove_image" value="1"> Bild entfernen
                </label>
            </div>
        @endif
        <input type="file" id="plot-image" name="image" accept="image/jpeg,image/png,image/webp">
        <x-input-error :messages="$errors->get('image')" class="mt-2" />
    </div>

    <label class="flex items-center gap-2 text-stone-700">
        <input type="hidden" name="public_visible" value="0">
        <input type="checkbox" name="public_visible" value="1" @checked(old('public_visible', $character->public_visible))>
        Im öffentlichen Heldenverzeichnis sichtbar
    </label>
</form>

@if ($character->exists)
    @if ($character->public_visible)
        <p class="mt-3 text-sm">
            <a href="{{ route('public.plot-character', $character) }}" target="_blank" rel="noopener" class="underline">
                <i class="external alternate icon"></i> Öffentliche Seite ansehen
            </a>
        </p>
    @endif

    <x-admin.inspector-delete :action="route('admin.plot-characters.destroy', $character)"
        title="Plot-Charakter löschen" :confirm="'Plot-Charakter „'.$character->name.'“ endgültig löschen?'" />
@endif

<div data-modal-actions hidden>
    <button type="submit" form="plot-character-form" class="ui primary button">Speichern</button>
</div>
