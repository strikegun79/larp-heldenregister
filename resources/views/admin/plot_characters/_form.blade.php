<span data-modal-title hidden>{{ $character->exists ? 'Plot-Charakter bearbeiten: '.$character->name : 'Neuer Plot-Charakter' }}</span>

@php
    // Hilfsfunktion für Auswahllisten (Konstanten aus dem Model).
    $select = fn (string $field, array $options) => collect($options)
        ->map(fn ($label, $value) => '<option value="'.e($value).'"'.(old($field, $character->{$field}) === $value ? ' selected' : '').'>'.e($label).'</option>')
        ->implode('');
@endphp

<form id="plot-character-form" method="POST" enctype="multipart/form-data"
      action="{{ $character->exists ? route('admin.plot-characters.update', $character) : route('admin.plot-characters.store') }}"
      class="ui form space-y-4">
    @csrf
    @if ($character->exists) @method('PUT') @endif

    {{-- Grunddaten --}}
    <h4 class="ui dividing header">Grunddaten</h4>

    <div class="field">
        <label for="plot-name">Name</label>
        <input type="text" id="plot-name" name="name" value="{{ old('name', $character->name) }}" maxlength="100" required>
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="field">
        <label for="plot-title">Beiname / Titel <span class="text-stone-400 font-normal">(z.&nbsp;B. „Bewahrer der Historie“)</span></label>
        <input type="text" id="plot-title" name="title" value="{{ old('title', $character->title) }}" maxlength="150">
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="two fields">
        <div class="field">
            <label for="plot-type">Typ</label>
            <select id="plot-type" name="character_type" class="ui dropdown">{!! $select('character_type', \App\Models\PlotCharacter::TYPES) !!}</select>
        </div>
        <div class="field">
            <label for="plot-status">Status</label>
            <select id="plot-status" name="status" class="ui dropdown">{!! $select('status', \App\Models\PlotCharacter::STATUSES) !!}</select>
        </div>
    </div>

    <div class="two fields">
        <div class="field">
            <label for="plot-species">Volk / Wesen</label>
            <select id="plot-species" name="species" class="ui dropdown">{!! $select('species', \App\Models\PlotCharacter::SPECIES) !!}</select>
        </div>
        <div class="field">
            <label for="plot-alignment">Gesinnung / Verhältnis</label>
            <select id="plot-alignment" name="alignment" class="ui dropdown">{!! $select('alignment', \App\Models\PlotCharacter::ALIGNMENTS) !!}</select>
        </div>
    </div>

    <div class="two fields">
        <div class="field">
            <label for="plot-class">Klasse <span class="text-stone-400 font-normal">(z.&nbsp;B. Magier)</span></label>
            <input type="text" id="plot-class" name="class_name" value="{{ old('class_name', $character->class_name) }}" maxlength="100">
        </div>
        <div class="field">
            <label for="plot-home">Heimatort</label>
            <input type="text" id="plot-home" name="home" value="{{ old('home', $character->home) }}" maxlength="150">
        </div>
    </div>

    <div class="two fields">
        <div class="field">
            <label for="plot-faction">Zugehörigkeit / Fraktion</label>
            <input type="text" id="plot-faction" name="faction" value="{{ old('faction', $character->faction) }}" maxlength="150" placeholder="z. B. Akademie, Königshof">
        </div>
        <div class="field">
            <label for="plot-location">Bekannter Aufenthaltsort</label>
            <input type="text" id="plot-location" name="location" value="{{ old('location', $character->location) }}" maxlength="150">
        </div>
    </div>

    <p class="text-xs text-stone-500 -mb-2">Datumsangaben als Freitext, z.&nbsp;B. „Unbekannt (frühester Tagebucheintrag: 05.08.2018)“. „Unbekannt“ wird öffentlich ausgeblendet.</p>
    <div class="three fields">
        <div class="field">
            <label for="plot-first-seen">Erste Erblickung</label>
            <input type="text" id="plot-first-seen" name="first_seen" value="{{ old('first_seen', $character->first_seen) }}" maxlength="150">
        </div>
        <div class="field">
            <label for="plot-missing-since">Verschollen / verstorben seit</label>
            <input type="text" id="plot-missing-since" name="missing_since" value="{{ old('missing_since', $character->missing_since) }}" maxlength="150">
        </div>
        <div class="field">
            <label for="plot-last-seen">Letzte Sichtung</label>
            <input type="text" id="plot-last-seen" name="last_seen" value="{{ old('last_seen', $character->last_seen) }}" maxlength="150">
        </div>
    </div>

    {{-- Steckbrief --}}
    <h4 class="ui dividing header">Steckbrief</h4>

    <div class="field">
        <label for="plot-description">Steckbrief / Hintergrund <span class="text-stone-400 font-normal">(öffentlich – für Kinder verständlich schreiben)</span></label>
        <textarea id="plot-description" name="description" rows="10" maxlength="10000">{{ old('description', $character->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="field">
        <label for="plot-quote">Bekanntester Ausspruch</label>
        <input type="text" id="plot-quote" name="quote" value="{{ old('quote', $character->quote) }}" maxlength="500">
    </div>

    <div class="field">
        <label for="plot-motto">Leitspruch <span class="text-stone-400 font-normal">(kursive Schlusszeile)</span></label>
        <input type="text" id="plot-motto" name="motto" value="{{ old('motto', $character->motto) }}" maxlength="500">
    </div>

    {{-- Listen --}}
    <h4 class="ui dividing header">Listen <span class="text-stone-400 font-normal text-sm">(ein Eintrag pro Zeile)</span></h4>

    @foreach ([
        'known_titles' => 'Bekannte Titel',
        'abilities' => 'Besondere Fähigkeiten',
        'artifacts' => 'Bekannte Artefakte',
        'connections' => 'Bekannte Verbindungen',
    ] as $field => $label)
        <div class="field">
            <label for="plot-{{ $field }}">{{ $label }}</label>
            <textarea id="plot-{{ $field }}" name="{{ $field }}" rows="3" maxlength="3000">{{ old($field, $character->listText($field)) }}</textarea>
            <x-input-error :messages="$errors->get($field)" class="mt-2" />
        </div>
    @endforeach

    {{-- Chronik --}}
    <h4 class="ui dividing header">Chronik</h4>

    <div class="field">
        <label for="plot-chronicle">Erwähnungen in Brans Tagebuch / Chronikeinträge</label>
        <textarea id="plot-chronicle" name="chronicle" rows="4" maxlength="10000"
                  placeholder="26.09.2026 | Auf der Spur von Haus Aldekerk | https://…">{{ old('chronicle', $character->chronicleText()) }}</textarea>
        <p class="text-xs text-stone-500 mt-1">Ein Eintrag pro Zeile: <code>Datum | Titel | Link</code>. Datum und Link sind optional.</p>
        <x-input-error :messages="$errors->get('chronicle')" class="mt-2" />
    </div>

    {{-- Plothintergrund --}}
    <h4 class="ui dividing header">Plothintergrund</h4>

    <div class="field">
        <label for="plot-public-lore">📖 Was die Waldritter wissen <span class="text-stone-400 font-normal">(öffentlich)</span></label>
        <textarea id="plot-public-lore" name="public_lore" rows="4" maxlength="10000">{{ old('public_lore', $character->public_lore) }}</textarea>
    </div>

    <div class="field">
        <label for="plot-gm-notes">🔒 SL-Planung <span class="text-stone-400 font-normal">(nur Plot-Schreiber, Bürokrat, Projektleitung, Spielleitung)</span></label>
        <textarea id="plot-gm-notes" name="gm_notes" rows="4" maxlength="10000">{{ old('gm_notes', $character->gm_notes) }}</textarea>
    </div>

    {{-- Bild & Sichtbarkeit --}}
    <h4 class="ui dividing header">Bild &amp; Sichtbarkeit</h4>

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
