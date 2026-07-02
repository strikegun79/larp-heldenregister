<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">
                Profil: {{ trim($user->name . ' ' . $user->lastname) }}
            </h2>
            <a href="{{ route('admin.users.index') }}" class="ui basic button">
                <i class="arrow left icon"></i> Nutzerliste
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-300 rounded text-green-800 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Rollen & Konto-Status --}}
            <div class="p-4 sm:p-6 bg-white/60 border-2 border-[#5a3a22]/40 rounded-lg">
                <h3 class="font-uncial text-lg text-waldritter mb-3">Rollen & Status</h3>
                <div class="flex flex-wrap gap-2 mb-3">
                    @forelse ($user->roles as $role)
                        <span class="ui label">{{ $role->label }}</span>
                    @empty
                        <span class="text-stone-500 text-sm">Keine Rolle zugewiesen.</span>
                    @endforelse
                </div>
                <p class="text-sm text-stone-600">
                    Konto: <strong>{{ $user->activated ? 'aktiviert' : 'deaktiviert' }}</strong>
                    &nbsp;·&nbsp;
                    Registriert: {{ $user->created_at->locale('de')->isoFormat('D. MMMM YYYY') }}
                </p>
                <p class="text-xs text-stone-400 mt-1">
                    Rollen und Aktivierung können im <a href="#" data-modal-url="{{ route('admin.users.edit', $user) }}" class="underline text-waldritter">Bearbeitungs-Modal</a> geändert werden.
                </p>
            </div>

            {{-- Stammdaten bearbeiten --}}
            <div class="p-4 sm:p-6 bg-white/60 border-2 border-[#5a3a22]/40 rounded-lg">
                <h3 class="font-uncial text-lg text-waldritter mb-4">Stammdaten</h3>

                <form method="POST" action="{{ route('admin.users.profile.update', $user) }}" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="name" value="Vorname" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                          :value="old('name', $user->name)" required autocomplete="given-name" />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <x-input-label for="lastname" value="Nachname" />
                            <x-text-input id="lastname" name="lastname" type="text" class="mt-1 block w-full"
                                          :value="old('lastname', $user->lastname)" autocomplete="family-name" />
                            <x-input-error class="mt-2" :messages="$errors->get('lastname')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="email" value="E-Mail" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                      :value="old('email', $user->email)" required autocomplete="username" />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        @if (! $user->hasVerifiedEmail())
                            <p class="text-xs text-amber-700 mt-1">E-Mail-Adresse ist nicht verifiziert.</p>
                        @endif
                    </div>

                    <div>
                        <x-input-label for="phone" value="Mobil / Telefon" />
                        <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full"
                                      :value="old('phone', $user->phone)" autocomplete="tel" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>

                    <div>
                        <h4 class="font-uncial text-base text-waldritter mb-3">Anschrift</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="street" value="Straße" />
                                <x-text-input id="street" name="street" type="text" class="mt-1 block w-full"
                                              :value="old('street', $user->street)" />
                                <x-input-error class="mt-2" :messages="$errors->get('street')" />
                            </div>
                            <div>
                                <x-input-label for="house_number" value="Hausnummer" />
                                <x-text-input id="house_number" name="house_number" type="text" class="mt-1 block w-full"
                                              :value="old('house_number', $user->house_number)" />
                                <x-input-error class="mt-2" :messages="$errors->get('house_number')" />
                            </div>
                            <div>
                                <x-input-label for="zip" value="PLZ" />
                                <x-text-input id="zip" name="zip" type="text" class="mt-1 block w-full"
                                              :value="old('zip', $user->zip)" maxlength="10" />
                                <x-input-error class="mt-2" :messages="$errors->get('zip')" />
                            </div>
                            <div>
                                <x-input-label for="city" value="Ort" />
                                <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                                              :value="old('city', $user->city)" />
                                <x-input-error class="mt-2" :messages="$errors->get('city')" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <x-primary-button>Speichern</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Konto löschen (AUTH-14: Lösch-Button gehört nicht in die Übersicht) --}}
            @if (! $user->hasRole('admin') && $user->id !== Auth::id() && ! $user->trashed())
            <div class="p-4 sm:p-6 bg-white/60 border-2 border-red-200 rounded-lg">
                <h3 class="font-uncial text-lg text-red-800 mb-2">Konto löschen</h3>
                <p class="text-sm text-stone-600 mb-4">
                    Das Konto wird soft-gelöscht und kann später wiederhergestellt werden.
                    Admin-Konten und das eigene Konto können nicht gelöscht werden.
                </p>
                <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}"
                      data-confirm="Konto von {{ trim($user->name . ' ' . $user->lastname) }} wirklich löschen?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ui red button">
                        <i class="trash icon"></i> Konto löschen
                    </button>
                </form>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
