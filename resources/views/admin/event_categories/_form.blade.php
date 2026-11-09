<span data-modal-title hidden>{{ $category->exists ? 'Kategorie bearbeiten: '.$category->name : 'Neue Kategorie' }}</span>

<form id="category-form" method="POST"
      action="{{ $category->exists ? route('admin.event-categories.update', $category) : route('admin.event-categories.store') }}"
      class="ui form space-y-4">
    @csrf
    @if ($category->exists) @method('PUT') @endif

    <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name', $category->name) }}" required>
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="field">
        <label>Beschreibung</label>
        <input type="text" name="description" value="{{ old('description', $category->description) }}">
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>
</form>

<div data-modal-actions hidden>
    <button type="submit" form="category-form" class="ui primary button">Speichern</button>
</div>
