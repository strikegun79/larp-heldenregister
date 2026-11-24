<span data-modal-title hidden>{{ $type->exists ? 'Buchungsart bearbeiten: '.$type->description : 'Neue Buchungsart' }}</span>

<form id="ep-type-form" method="POST"
      action="{{ $type->exists ? route('admin.ep-transaction-types.update', $type) : route('admin.ep-transaction-types.store') }}"
      class="ui form space-y-4">
    @csrf
    @if ($type->exists) @method('PUT') @endif

    <div class="field">
        <label>Beschreibung</label>
        <input type="text" name="description" value="{{ old('description', $type->description) }}" required maxlength="100">
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="field">
        <label class="flex items-center gap-2">
            <input type="hidden" name="is_credit" value="0">
            <input type="checkbox" name="is_credit" value="1"
                   @checked(old('is_credit', $type->is_credit))>
            Gutschrift (EP werden addiert, nicht abgezogen)
        </label>
        <x-input-error :messages="$errors->get('is_credit')" class="mt-2" />
    </div>

    {{-- Issue #28: Regelwerk-Standard, wird beim EP-Buchen im Heldenbuch vorbelegt --}}
    <div class="field">
        <label for="ep-type-default">Standard-EP (optional)</label>
        <input type="number" id="ep-type-default" name="default_ep" min="1" max="9999" step="1"
               value="{{ old('default_ep', $type->default_ep) }}" placeholder="kein Standardwert" style="max-width:10rem">
        <p class="text-xs text-stone-500 mt-1">Wird beim Buchen im Heldenbuch automatisch ins EP-Feld eingetragen und lässt sich dort ändern.</p>
        <x-input-error :messages="$errors->get('default_ep')" class="mt-2" />
    </div>
</form>

{{-- Löschen nur im Inspektor (?inspector=1), Rückfrage und Sperrgrund wie in der bisherigen Liste --}}
@if ($type->exists)
    @php($usedBy = $type->transactions()->count())
    <x-admin.inspector-delete :action="route('admin.ep-transaction-types.destroy', $type)"
        title="Buchungsart löschen" :confirm="'Buchungsart '.$type->description.' löschen?'"
        :blocked="$usedBy ? 'Nicht löschbar: '.$usedBy.' EP-Buchung(en) nutzen diese Buchungsart.' : null" />
@endif

<div data-modal-actions hidden>
    <button type="submit" form="ep-type-form" class="ui primary button">Speichern</button>
</div>
