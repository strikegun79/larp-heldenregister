<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" value="E-Mail" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                          :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Passwort" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                       class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-600"
                       name="remember">
                <span class="ms-2 text-sm text-gray-600">Angemeldet bleiben</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-600"
                   href="{{ route('password.request') }}">
                    Passwort vergessen?
                </a>
            @endif

            <x-primary-button class="ms-3">Anmelden</x-primary-button>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="mt-6 text-center text-sm text-gray-600">
            Noch kein Konto?
            <a href="{{ route('register') }}"
               class="underline text-waldritter hover:text-[#3a200e] rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-600">
                Jetzt registrieren
            </a>
        </div>
    @endif

    {{-- PUB-09: Heldensuche ohne Anmeldung --}}
    <div class="mt-8 pt-6 border-t-2 border-[#5a3a22]/20">
        <h2 class="font-uncial text-waldritter text-base mb-2 text-center">Was ist das Heldenregister?</h2>
        <p class="text-sm text-stone-600 leading-snug mb-4 text-center">
            Das Heldenregister der Waldritter erfasst alle LARP-Helden mit ihren
            Fertigkeiten und Abenteuern. Jeder Held hat einen eigenen 6-stelligen
            Code – zum Beispiel auf dem <strong>Heldenausweis</strong> oder als
            <strong>QR-Code</strong>.
        </p>
        <a href="{{ route('public.hero.search') }}"
           class="ui fluid waldritter button"
           style="background:#5a3a22; color:#fff; display:block; text-align:center;
                  border-radius:.375rem; padding:.625rem 1rem; font-family:inherit;
                  text-decoration:none; font-size:.95rem;">
            &#x2692; Helden-Code eingeben &rarr; Profil aufrufen
        </a>
        <p class="text-xs text-stone-400 mt-2 text-center">
            Keine Anmeldung nötig – einfach Code oder Heldennamen eingeben.
        </p>
    </div>
</x-guest-layout>
