<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">
            Nutzer bearbeiten: {{ trim("{$user->name} {$user->lastname}") }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
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
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           @checked(in_array($role->id, old('roles', $assigned)))>
                                    {{ $role->label }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('roles')" class="mt-2" />
                    </div>

                    <label class="flex items-center gap-2 text-stone-700">
                        <input type="checkbox" name="activated" value="1"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                               @checked(old('activated', $user->activated))>
                        Konto aktiviert
                    </label>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Speichern</x-primary-button>
                        <a href="{{ route('admin.users.index') }}" class="text-sm text-stone-600 hover:underline">Abbrechen</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
