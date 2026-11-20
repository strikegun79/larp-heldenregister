<span data-modal-title hidden>Status bearbeiten: {{ $status->description }}</span>

<form id="status-form" method="POST"
      action="{{ route('admin.event-statuses.update', $status) }}"
      class="ui form space-y-4">
    @csrf
    @method('PUT')

    <div class="field">
        <label>Bezeichnung</label>
        <input type="text" name="description" value="{{ old('description', $status->description) }}" required maxlength="100">
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="field">
        <label>Farbe</label>
        <div class="flex items-center gap-3">
            <input type="color" name="color" value="{{ old('color', $status->color) }}" class="h-10 w-16 cursor-pointer rounded border border-stone-300">
            <span class="text-sm text-stone-500">Hex-Farbwert (z. B. #a2de00)</span>
        </div>
        <x-input-error :messages="$errors->get('color')" class="mt-2" />
    </div>
</form>

{{-- Löschen nur im Inspektor (?inspector=1), Rückfrage und Sperrgrund wie in der bisherigen Liste --}}
@php($usedBy = $status->adventures()->count())
<x-admin.inspector-delete :action="route('admin.event-statuses.destroy', $status)"
    title="Status löschen" :confirm="'Status „'.$status->description.'“ löschen?'"
    :blocked="$usedBy ? 'Nicht löschbar: '.$usedBy.' Abenteuer haben diesen Status.' : null" />

<div data-modal-actions hidden>
    <button type="submit" form="status-form" class="ui primary button">Speichern</button>
</div>
