<span data-modal-title hidden>Nutzer einladen</span>

<p class="text-sm text-stone-600 mb-4">
    Der neue Nutzer erhält eine E-Mail mit einem Link zum Setzen seines Passworts.
</p>

<form id="user-create-form" method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
    @csrf

    <div>
        <x-input-label for="invite_name" value="Vorname *" />
        <x-text-input id="invite_name" name="name" type="text" class="mt-1 block w-full"
                      value="{{ old('name') }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="invite_lastname" value="Nachname" />
        <x-text-input id="invite_lastname" name="lastname" type="text" class="mt-1 block w-full"
                      value="{{ old('lastname') }}" />
        <x-input-error :messages="$errors->get('lastname')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="invite_email" value="E-Mail-Adresse *" />
        <x-text-input id="invite_email" name="email" type="email" class="mt-1 block w-full"
                      value="{{ old('email') }}" required />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <span class="block font-medium text-stone-700 mb-2">Rollen</span>
        <div class="grid grid-cols-2 gap-2">
            @foreach ($roles as $role)
                <label class="flex items-center gap-2 text-stone-700">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                           @checked(in_array($role->id, old('roles', [])))>
                    {{ $role->label }}
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('roles')" class="mt-2" />
    </div>
</form>

<div data-modal-actions hidden>
    <button type="submit" form="user-create-form" class="ui primary button">Einladen</button>
</div>
