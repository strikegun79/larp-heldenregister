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
</form>

<div data-modal-actions hidden>
    <button type="submit" form="ep-type-form" class="ui primary button">Speichern</button>
</div>
