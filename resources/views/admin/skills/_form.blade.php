<span data-modal-title hidden>{{ $skill->exists ? 'Fertigkeit bearbeiten: '.$skill->name : 'Neue Fertigkeit' }}</span>

<form id="skill-form" method="POST"
      action="{{ $skill->exists ? route('admin.skills.update', $skill) : route('admin.skills.store') }}"
      class="ui form space-y-4">
    @csrf
    @if ($skill->exists) @method('PUT') @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="field sm:col-span-2">
            <label>Name *</label>
            <input type="text" name="name" value="{{ old('name', $skill->name) }}" required maxlength="100">
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div class="field sm:col-span-2">
            <label>Beschreibung</label>
            <textarea name="description" rows="3" maxlength="1000">{{ old('description', $skill->description) }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-1" />
        </div>

        <div class="field">
            <label>EP-Kosten *</label>
            <input type="number" name="ep_costs" value="{{ old('ep_costs', $skill->ep_costs ?? 0) }}" required min="0">
            <x-input-error :messages="$errors->get('ep_costs')" class="mt-1" />
        </div>

        <div class="field">
            <label>Level *</label>
            <input type="number" name="level" value="{{ old('level', $skill->level ?? 1) }}" required min="1" max="10">
            <x-input-error :messages="$errors->get('level')" class="mt-1" />
        </div>

        <div class="field">
            <label>Masterclass</label>
            <select name="hero_class_id">
                <option value="">— keine —</option>
                @foreach ($heroClasses as $class)
                    <option value="{{ $class->id }}"
                            @selected(old('hero_class_id', $skill->hero_class_id) == $class->id)>
                        {{ $class->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('hero_class_id')" class="mt-1" />
        </div>

        <div class="field">
            <label>Perlenfarbe</label>
            <select name="perl_color_id">
                <option value="">— keine —</option>
                @foreach ($perlColors as $color)
                    <option value="{{ $color->id }}"
                            @selected(old('perl_color_id', $skill->perl_color_id) == $color->id)>
                        {{ $color->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('perl_color_id')" class="mt-1" />
        </div>

        <div class="field">
            <label>Perlenanzahl</label>
            <input type="number" name="perl_count" value="{{ old('perl_count', $skill->perl_count) }}" min="0">
            <x-input-error :messages="$errors->get('perl_count')" class="mt-1" />
        </div>
    </div>

    <div>
        <span class="block font-medium text-stone-700 mb-2">Klassen (Wer kann diese Fertigkeit lernen?)</span>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            @foreach ($heroClasses as $class)
                <label class="flex items-center gap-2 text-stone-700">
                    <input type="checkbox" name="classes[]" value="{{ $class->id }}"
                           @checked(in_array($class->id, old('classes', $assigned)))>
                    {{ $class->name }}
                </label>
            @endforeach
        </div>
    </div>

    {{-- SKILL-10: Voraussetzungen pro Klasse --}}
    <div id="prereq-sections">
        <span class="block font-medium text-stone-700 mb-2">Voraussetzungen pro Klasse</span>
        <p class="text-xs text-stone-400 mb-3">
            Für jede zugeordnete Klasse können eigene Voraussetzungen definiert werden.
            Wähle zuerst die Klassen oben aus.
        </p>
        @foreach ($heroClasses as $class)
            @php($classAssigned = in_array($class->id, old('classes', $assigned)))
            <div class="prereq-class-section mb-4 border border-stone-200 rounded p-3
                        {{ $classAssigned ? '' : 'hidden' }}"
                 data-class-id="{{ $class->id }}">
                <div class="text-xs font-semibold text-waldritter uppercase tracking-wide mb-2">
                    Voraussetzungen für {{ $class->name }}
                </div>
                @forelse ($allSkills->groupBy(fn ($s) => $s->heroClass?->name ?? 'Ohne Klasse') as $groupName => $groupSkills)
                    <div class="mb-2">
                        <div class="text-xs text-stone-400 mb-1">{{ $groupName }}</div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-1">
                            @foreach ($groupSkills as $s)
                                <label class="flex items-center gap-2 text-stone-700 text-sm">
                                    <input type="checkbox"
                                           name="prereqs[{{ $class->id }}][]"
                                           value="{{ $s->id }}"
                                           @checked(in_array($s->id, old("prereqs.{$class->id}", $assignedPrerequisitesByClass[$class->id] ?? [])))>
                                    {{ $s->name }} (Lv.{{ $s->level }})
                                </label>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Keine anderen Fertigkeiten vorhanden.</p>
                @endforelse
            </div>
        @endforeach
    </div>

    <script>
    (function () {
        function togglePrereqSection(classId, visible) {
            document.querySelectorAll('.prereq-class-section[data-class-id="' + classId + '"]')
                .forEach(function (el) {
                    el.classList.toggle('hidden', !visible);
                    if (!visible) {
                        el.querySelectorAll('input[type=checkbox]').forEach(function (cb) {
                            cb.checked = false;
                        });
                    }
                });
        }
        document.querySelectorAll('input[name="classes[]"]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                togglePrereqSection(this.value, this.checked);
            });
        });
    }());
    </script>
</form>

{{-- SKILL-08: Symbol hochladen (separater Upload, kein Cropper) --}}
@if ($skill->exists)
<div class="ui segment">
    <label class="block font-medium text-stone-700 mb-2">Symbol (100×100 px)</label>
    <div class="flex items-center gap-4 flex-wrap">
        @if ($skill->icon_url)
            <img src="{{ $skill->icon_url }}" alt="Symbol"
                 class="w-12 h-12 object-cover rounded border border-stone-200" loading="lazy">
            <form method="POST" action="{{ route('admin.skills.icon.destroy', $skill) }}" data-refresh-modal>
                @csrf @method('DELETE')
                <button type="submit" class="ui mini red basic button">
                    <i class="trash icon"></i> Symbol löschen
                </button>
            </form>
        @else
            <div class="w-12 h-12 rounded border-2 border-dashed border-stone-300 flex items-center justify-center text-stone-400">
                <i class="image icon"></i>
            </div>
        @endif
        <form method="POST" action="{{ route('admin.skills.icon.store', $skill) }}"
              enctype="multipart/form-data" data-refresh-modal>
            @csrf
            <label class="ui mini basic button cursor-pointer">
                <i class="upload icon"></i> {{ $skill->icon_url ? 'Ersetzen' : 'Hochladen' }}
                <input type="file" name="icon" accept="image/png,image/jpeg" class="hidden"
                       onchange="this.form.requestSubmit()">
            </label>
        </form>
    </div>
    <p class="text-xs text-stone-400 mt-1">PNG oder JPG, wird auf 100×100 px skaliert.</p>
</div>
@endif

<div data-modal-actions hidden>
    <button type="submit" form="skill-form" class="ui primary button">Speichern</button>
</div>
