<section>
    <header>
        <h2 class="font-uncial text-lg text-waldritter">Angaben der erziehungsberechtigten Person</h2>
        <p class="mt-1 text-sm text-stone-600">Diese Angaben beziehen sich auf die erziehungsberechtigte Person – nicht auf das Kind.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="name" value="Vorname" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              :value="old('name', $user->name)" required autofocus autocomplete="given-name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label for="lastname" value="Nachname" />
                <x-text-input id="lastname" name="lastname" type="text" class="mt-1 block w-full"
                              :value="old('lastname', $user->lastname)" required autocomplete="family-name" />
                <x-input-error class="mt-2" :messages="$errors->get('lastname')" />
            </div>
        </div>

        <div>
            <x-input-label for="phone" value="Mobil / Telefon" />
            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full"
                          :value="old('phone', $user->phone)" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div class="pt-2">
            <h3 class="font-uncial text-base text-waldritter">Anschrift</h3>
            <p class="text-sm text-gray-500 mb-3">Diese Anschrift wird für Anmeldung, Kontakt und rechtliche Einwilligungen verwendet.</p>

            @if (! auth()->user()->hasCompleteAddress())
                <div class="mb-3 p-3 bg-amber-50 border border-amber-300 rounded text-sm text-amber-800">
                    Deine Kontaktdaten sind unvollständig. Bitte ergänze sie, um Spieler zu Abenteuern anmelden zu können.
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="street" value="Straße" />
                    <x-text-input id="street" name="street" type="text" class="mt-1 block w-full"
                                  :value="old('street', $user->street)" autocomplete="street-address" />
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
                                  :value="old('zip', $user->zip)" autocomplete="postal-code" maxlength="10" />
                    <x-input-error class="mt-2" :messages="$errors->get('zip')" />
                </div>
                <div>
                    <x-input-label for="city" value="Ort" />
                    <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                                  :value="old('city', $user->city)" autocomplete="address-level2" />
                    <x-input-error class="mt-2" :messages="$errors->get('city')" />
                </div>
            </div>

            <p class="mt-3 text-xs text-gray-400">
                Wir benötigen diese Daten zur Durchführung der Veranstaltung, zur Kontaktaufnahme und für rechtlich erforderliche Einwilligungen.
            </p>
        </div>

        <div>
            <x-input-label for="email" value="E-Mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-stone-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-stone-600 hover:text-stone-800 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Speichern</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-stone-600"
                >Gespeichert.</p>
            @endif
        </div>
    </form>
</section>
