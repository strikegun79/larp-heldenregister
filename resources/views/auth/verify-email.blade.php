<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Danke für deine Registrierung! Bitte bestätige deine E-Mail-Adresse, indem du auf den Link klickst, den wir dir gerade geschickt haben. Falls du keine E-Mail erhalten hast, senden wir dir gerne eine neue.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Ein neuer Bestätigungslink wurde an deine E-Mail-Adresse gesendet.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <div>
                <x-primary-button>Bestätigungs-E-Mail erneut senden</x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Abmelden
            </button>
        </form>
    </div>
</x-guest-layout>
