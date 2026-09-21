<span data-modal-title hidden>Task bearbeiten: {{ $definition->label }}</span>

<form method="POST" action="{{ route('admin.task-manager.update', $definition) }}"
      data-reload id="task-definition-form">
    @csrf
    @method('PUT')

    <div class="space-y-4">

        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1" for="td_label">Bezeichnung</label>
            <input type="text" name="label" id="td_label"
                   value="{{ old('label', $definition->label) }}"
                   class="w-full border border-stone-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-waldritter/50"
                   required maxlength="100">
            @error('label')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-stone-700 mb-1" for="td_description">Beschreibung</label>
            <textarea name="description" id="td_description" rows="2" maxlength="500"
                      class="w-full border border-stone-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-waldritter/50">{{ old('description', $definition->description) }}</textarea>
            @error('description')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1" for="td_days">Standard-Tage</label>
                <input type="number" name="default_days" id="td_days"
                       value="{{ old('default_days', $definition->default_days) }}"
                       min="0" max="999"
                       class="w-full border border-stone-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-waldritter/50"
                       required>
                @error('default_days')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1" for="td_direction">Richtung</label>
                <select name="default_direction" id="td_direction"
                        class="w-full border border-stone-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-waldritter/50">
                    <option value="before" @selected(old('default_direction', $definition->default_direction) === 'before')>vorher</option>
                    <option value="after"  @selected(old('default_direction', $definition->default_direction) === 'after')>nachher</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1" for="td_reference">Bezug</label>
                <select name="default_reference" id="td_reference"
                        class="w-full border border-stone-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-waldritter/50">
                    <option value="start_at" @selected(old('default_reference', $definition->default_reference) === 'start_at')>Beginn</option>
                    <option value="end_at"   @selected(old('default_reference', $definition->default_reference) === 'end_at')>Ende</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-1">
            <div class="ui checkbox">
                <input type="checkbox" name="is_enabled" id="td_enabled" value="1"
                       @checked(old('is_enabled', $definition->is_enabled))>
                <label for="td_enabled" class="text-sm text-stone-700">Task im Taskmanager anzeigen</label>
            </div>
        </div>

    </div>

    <div class="mt-5 flex gap-2">
        <button type="submit" class="ui primary button">Speichern</button>
    </div>
</form>

<div data-modal-actions hidden>
    <button type="submit" form="task-definition-form" class="ui primary button">Speichern</button>
</div>
