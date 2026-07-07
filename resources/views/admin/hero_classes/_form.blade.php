<span data-modal-title hidden>{{ $class->exists ? 'Klasse bearbeiten: '.$class->name : 'Neue Klasse' }}</span>

<form id="hero-class-form" method="POST"
      action="{{ $class->exists ? route('admin.hero-classes.update', $class) : route('admin.hero-classes.store') }}"
      class="ui form space-y-4">
    @csrf
    @if ($class->exists) @method('PUT') @endif

    <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name', $class->name) }}" required>
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="field">
        <label>Slug (Maschinen-Schlüssel, z.&nbsp;B. „warrior")</label>
        <input type="text" name="slug" value="{{ old('slug', $class->slug) }}" required>
        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
    </div>

    <div class="field">
        <label>EP-Kosten (beim Hinzufügen zu einem Helden)</label>
        <input type="number" name="ep_cost" min="0" value="{{ old('ep_cost', $class->ep_cost ?? 5) }}" required>
        <x-input-error :messages="$errors->get('ep_cost')" class="mt-2" />
    </div>

    {{-- Bändchenfarbe --}}
    <div class="field">
        <label>Bändchenfarbe</label>
        <div class="flex items-center gap-3">
            <input type="color" name="ribbon_color"
                   value="{{ old('ribbon_color', $class->ribbon_color ?? '#5a3a22') }}"
                   class="h-10 w-16 rounded border border-stone-300 cursor-pointer p-0.5">
            <span class="text-xs text-stone-500">Hex-Farbe des Klassenbandes (z.&nbsp;B. Randfarbe)</span>
        </div>
        <x-input-error :messages="$errors->get('ribbon_color')" class="mt-2" />
    </div>

    <label class="flex items-center gap-2 text-stone-700">
        <input type="checkbox" name="disabled" value="1" @checked(old('disabled', $class->disabled))>
        Deaktiviert (nicht mehr für neue Helden wählbar)
    </label>
</form>

{{-- Klassenband-Bild (nur bei bestehender Klasse, separates Upload-Formular) --}}
@if ($class->exists)
    <div class="mt-4 pt-4 border-t border-stone-200">
        <p class="text-sm font-medium text-stone-700 mb-2">Klassenband-Bild <span class="text-stone-400 font-normal">(162&times;600&thinsp;px, PNG oder JPG)</span></p>

        <div class="flex items-start gap-4">
            {{-- Vorschau --}}
            @php($ribbonUrl = $class->ribbonImageUrl())
            @if ($ribbonUrl)
                <div class="flex flex-col items-center gap-1">
                    <img src="{{ $ribbonUrl }}" alt="Klassenband {{ $class->name }}"
                         class="h-24 w-auto object-cover rounded border border-stone-200"
                         style="max-width:2.5rem" loading="lazy">
                    <span class="text-xs text-stone-400">Vorschau</span>
                </div>
            @else
                <div class="h-24 w-10 rounded border-2 border-dashed border-stone-300 flex items-center justify-center text-stone-400 text-xs">
                    –
                </div>
            @endif

            <div class="flex flex-col gap-2">
                {{-- Upload --}}
                <form method="POST" action="{{ route('admin.hero-classes.ribbon.store', $class) }}"
                      enctype="multipart/form-data" data-refresh-modal>
                    @csrf
                    <label class="ui mini basic button cursor-pointer">
                        <i class="upload icon"></i> {{ $class->ribbon_image ? 'Ersetzen' : 'Hochladen' }}
                        <input type="file" name="ribbon" accept="image/png,image/jpeg" class="hidden"
                               onchange="this.form.requestSubmit()">
                    </label>
                </form>

                {{-- Löschen (nur wenn ein eigenes Bild hochgeladen wurde) --}}
                @if ($class->ribbon_image)
                    <form method="POST" action="{{ route('admin.hero-classes.ribbon.destroy', $class) }}" data-refresh-modal>
                        @csrf @method('DELETE')
                        <button type="submit" class="ui mini red basic button">
                            <i class="trash icon"></i> Eigenes Bild löschen
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if (! $class->ribbon_image && $ribbonUrl)
            <p class="text-xs text-stone-400 mt-2">
                <i class="info circle icon"></i> Automatisch erkanntes Fallback-Bild aus <code>public/images/</code>.
            </p>
        @endif
    </div>
@endif

<div data-modal-actions hidden>
    <button type="submit" form="hero-class-form" class="ui primary button">Speichern</button>
</div>
