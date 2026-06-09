<h2 class="font-uncial text-2xl text-waldritter mb-1">{{ trim("{$user->name} {$user->lastname}") }}</h2>
<p class="text-sm text-stone-600 mb-4">{{ $user->email }}</p>

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div>
        <span class="block font-medium text-stone-700 mb-2">Rollen</span>
        <div class="grid grid-cols-2 gap-2">
            @foreach ($roles as $role)
                <label class="flex items-center gap-2 text-stone-700">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                           @checked(in_array($role->id, old('roles', $assigned)))>
                    {{ $role->label }}
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('roles')" class="mt-2" />
    </div>

    <label class="flex items-center gap-2 text-stone-700">
        <input type="checkbox" name="activated" value="1" @checked(old('activated', $user->activated))>
        Konto aktiviert
    </label>

    <div class="flex items-center gap-4">
        <button type="submit" class="ui primary button">Speichern</button>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-stone-600 hover:underline">Abbrechen</a>
    </div>
</form>
