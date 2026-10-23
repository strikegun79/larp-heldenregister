{{-- Gemeinsame Formularfelder für Anlegen/Bearbeiten einer Startseiten-Nachricht. --}}
<div class="field">
    <label for="motd_title">Titel (nur intern, für die Übersicht)</label>
    <input type="text" name="title" id="motd_title" maxlength="150" required
           value="{{ old('title', $motd->title) }}">
    <x-input-error :messages="$errors->get('title')" class="mt-1" />
</div>

<div class="field">
    <div class="ui toggle checkbox">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="motd_is_active" value="1"
               {{ old('is_active', $motd->is_active) ? 'checked' : '' }}>
        <label for="motd_is_active">Aktiv (deaktiviert automatisch die bisher aktive Nachricht)</label>
    </div>
</div>

<div class="two fields">
    <div class="field">
        <label for="motd_variant">Farbvariante</label>
        <select name="variant" id="motd_variant" class="ui dropdown">
            @foreach ($variants as $key => $variant)
                <option value="{{ $key }}" @selected(old('variant', $motd->variant) === $key)>{{ $variant['label'] }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('variant')" class="mt-1" />
    </div>
    <div class="field">
        <label>&nbsp;</label>
        <div class="ui checkbox">
            <input type="hidden" name="dismissible" value="0">
            <input type="checkbox" name="dismissible" id="motd_dismissible" value="1"
                   {{ old('dismissible', $motd->dismissible) ? 'checked' : '' }}>
            <label for="motd_dismissible">Benutzer dürfen die Nachricht schließen</label>
        </div>
    </div>
</div>

<div class="two fields">
    <div class="field">
        <label for="motd_start_at">Anzeigen ab (optional)</label>
        <input type="datetime-local" name="start_at" id="motd_start_at"
               value="{{ old('start_at', $motd->start_at?->format('Y-m-d\TH:i')) }}">
        <x-input-error :messages="$errors->get('start_at')" class="mt-1" />
    </div>
    <div class="field">
        <label for="motd_end_at">Anzeigen bis (optional)</label>
        <input type="datetime-local" name="end_at" id="motd_end_at"
               value="{{ old('end_at', $motd->end_at?->format('Y-m-d\TH:i')) }}">
        <x-input-error :messages="$errors->get('end_at')" class="mt-1" />
    </div>
</div>

<div class="field">
    <label for="motd_image">Banner-Bild (optional, PNG/JPG/WebP, max. 4 MB)</label>
    @if ($motd->image)
        <img src="{{ $motd->image_url }}" alt="Aktuelles Banner-Bild"
             class="mb-2 rounded border border-stone-300" style="max-height: 160px;">
        <div class="ui checkbox mb-2">
            <input type="hidden" name="remove_image" value="0">
            <input type="checkbox" name="remove_image" id="motd_remove_image" value="1">
            <label for="motd_remove_image">Bild entfernen</label>
        </div>
    @endif
    <input type="file" name="image" id="motd_image" accept="image/png,image/jpeg,image/webp">
    <p class="text-xs text-stone-500 mt-1">
        Empfohlen: 1200 × 320 px (für hochauflösende Bildschirme 2400 × 640 px). Angezeigt wird das Bild
        in voller Breite und maximal 320 px hoch; abweichende Formate werden oben/unten beschnitten.
    </p>
    <x-input-error :messages="$errors->get('image')" class="mt-1" />
</div>

<div class="field">
    <label for="motd_image_link">Link beim Klick auf das Bild (optional)</label>
    <input type="url" name="image_link" id="motd_image_link" maxlength="500"
           placeholder="https://…/adventures/12"
           value="{{ old('image_link', $motd->image_link) }}">
    <p class="text-xs text-stone-500 mt-1">
        Links auf das Heldenregister öffnen im selben Tab, externe Seiten in einem neuen Tab.
    </p>
    <x-input-error :messages="$errors->get('image_link')" class="mt-1" />
</div>

<div class="field">
    <label for="motd_html">Text</label>
    @include('partials._rich_editor', [
        'id'    => 'motd_html',
        'name'  => 'html',
        'value' => old('html', $motd->html),
        'links' => true,
    ])
    <x-input-error :messages="$errors->get('html')" class="mt-1" />
</div>
