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
</form>

<div data-modal-actions hidden>
    <button type="submit" form="skill-form" class="ui primary button">Speichern</button>
</div>
