<section class="space-y-6">
    <header>
        <h2 class="font-uncial text-lg text-waldritter">Konto löschen</h2>

        <p class="mt-1 text-sm text-stone-600">
            Sobald dein Konto gelöscht wird, werden alle Daten dauerhaft entfernt. Bitte sichere vorher alle wichtigen Daten.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Konto löschen</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="font-uncial text-lg text-waldritter">
                Konto wirklich löschen?
            </h2>

            <p class="mt-1 text-sm text-stone-600">
                Sobald dein Konto gelöscht wird, werden alle Daten dauerhaft entfernt. Bitte gib dein Passwort ein, um die Löschung zu bestätigen.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Passwort" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Passwort"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Abbrechen
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Konto löschen
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
