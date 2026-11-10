<x-guest-layout>
    <div class="mb-6 flex items-center gap-3">
        <div class="flex-shrink-0 text-3xl">⚠️</div>
        <div>
            <h2 class="text-lg font-semibold text-gray-800">
                @if ($alreadyVerified)
                    E-Mail-Adresse bereits bestätigt
                @else
                    Dieser Link ist nicht mehr gültig
                @endif
            </h2>
        </div>
    </div>

    @if ($alreadyVerified)
        <p class="text-sm text-gray-600 mb-4">
            Deine E-Mail-Adresse wurde bereits erfolgreich bestätigt. Du kannst dich jetzt direkt anmelden.
        </p>
        <a href="{{ route('login') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            Zur Anmeldung
        </a>
    @else
        <p class="text-sm text-gray-600 mb-2">
            Der Bestätigungslink ist abgelaufen oder wurde bereits verwendet.
        </p>
        <p class="text-sm text-gray-600 mb-6">
            Bestätigungslinks sind nur einmalig gültig und verfallen nach einiger Zeit. Bitte fordere unten einen neuen Link an – wir schicken ihn sofort an deine E-Mail-Adresse.
        </p>

        @auth
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Neuen Bestätigungslink anfordern
                </button>
            </form>
        @else
            <a href="{{ route('login') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Zur Anmeldung
            </a>
            <p class="mt-3 text-xs text-gray-500">
                Melde dich an, um einen neuen Bestätigungslink anzufordern.
            </p>
        @endauth
    @endif
</x-guest-layout>
