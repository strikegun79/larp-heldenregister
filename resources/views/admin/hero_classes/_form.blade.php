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

    <label class="flex items-center gap-2 text-stone-700">
        <input type="checkbox" name="disabled" value="1" @checked(old('disabled', $class->disabled))>
        Deaktiviert (nicht mehr für neue Helden wählbar)
    </label>
</form>

<div data-modal-actions hidden>
    <button type="submit" form="hero-class-form" class="ui primary button">Speichern</button>
</div>
