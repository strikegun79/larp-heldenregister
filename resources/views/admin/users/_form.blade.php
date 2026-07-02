<span data-modal-title hidden>Nutzer: {{ trim("{$user->name} {$user->lastname}") }}</span>
<p class="text-sm text-stone-600 mb-4">{{ $user->email }}</p>

<form id="user-edit-form" method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
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
</form>

<div data-modal-actions hidden>
    {{-- AUTH-14: Lösch-Button im Modal (nicht in der Übersicht) --}}
    @if ($user->id !== Auth::id())
        @if ($user->hasRole('admin'))
            <button type="button" class="ui basic red button disabled" title="Admin-Konten können nicht gelöscht werden." disabled>
                <i class="trash icon"></i> Löschen
            </button>
        @else
            <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" class="inline"
                  data-confirm="Konto von {{ trim($user->name . ' ' . $user->lastname) }} wirklich löschen?">
                @csrf @method('DELETE')
                <button type="submit" class="ui basic red button">
                    <i class="trash icon"></i> Löschen
                </button>
            </form>
        @endif
    @endif
    <button type="submit" form="user-edit-form" class="ui primary button">Speichern</button>
</div>
