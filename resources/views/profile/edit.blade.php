<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">
            Dein Profil
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="p-4 sm:p-8 bg-white/60 border-2 border-[#5a3a22]/40 rounded-lg">
                <div class="max-w-xl">
                    <h2 class="font-uncial text-lg text-waldritter">Deine Rollen</h2>
                    <p class="mt-1 text-sm text-stone-600">Diese Rechte sind deinem Konto zugewiesen. Vergeben werden sie in der Verwaltung.</p>
                    <div class="mt-4">
                        @forelse (auth()->user()->roles as $role)
                            <span class="ui label">{{ $role->label }}</span>
                        @empty
                            <span class="text-stone-500">Keine Rolle zugewiesen.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white/60 border-2 border-[#5a3a22]/40 rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white/60 border-2 border-[#5a3a22]/40 rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @if (auth()->user()->hasAnyRole('teamer', 'lehrmeister'))
                <div class="p-4 sm:p-8 bg-white/60 border-2 border-[#5a3a22]/40 rounded-lg">
                    <div class="max-w-xl">
                        <h2 class="font-uncial text-lg text-waldritter">Teamer-Benachrichtigungen</h2>
                        <p class="mt-1 text-sm text-stone-600">Erhalte eine Benachrichtigung, wenn du als Teamer zu einem Abenteuer eingeladen wirst.</p>
                        <form method="POST" action="{{ route('profile.update') }}" class="mt-4">
                            @csrf @method('PATCH')
                            <input type="hidden" name="name" value="{{ auth()->user()->name }}">
                            <input type="hidden" name="lastname" value="{{ auth()->user()->lastname }}">
                            <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                            <input type="hidden" name="phone" value="{{ auth()->user()->phone }}">
                            <label class="flex items-center gap-3 mt-2">
                                <input type="checkbox" name="teamer_notifications" value="1"
                                       @checked(auth()->user()->teamer_notifications ?? true)>
                                <span class="text-sm text-stone-700">Teamer-Einladungen per E-Mail und im Portal erhalten</span>
                            </label>
                            <button type="submit" class="ui primary button mt-4">Einstellung speichern</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- UI-36: Datenschutz & gespeicherte Daten --}}
            <div class="p-4 sm:p-8 bg-white/60 border-2 border-[#5a3a22]/40 rounded-lg">
                <div class="max-w-xl">
                    <h2 class="font-uncial text-lg text-waldritter">Deine Daten & Datenschutz</h2>
                    <p class="mt-1 text-sm text-stone-600">
                        Im Heldenregister werden folgende personenbezogene Daten zu deinem Konto gespeichert:
                    </p>
                    <ul class="mt-3 space-y-1 text-sm text-stone-700 list-disc list-inside">
                        <li>Name und E-Mail-Adresse (Pflicht für die Anmeldung)</li>
                        <li>Telefonnummer und Anschrift (für Kontakt und rechtliche Einwilligungen)</li>
                        <li>Angaben zu deinen Spielern und deren Helden (Spielbetrieb)</li>
                        <li>Anmeldungen zu Abenteuern (Teilnahmedokumentation)</li>
                    </ul>
                    <p class="mt-3 text-sm text-stone-600">
                        Diese Daten werden ausschließlich für die Organisation der Waldritter-Veranstaltungen verwendet
                        und nicht an Dritte weitergegeben.
                    </p>
                    <p class="mt-3 text-sm text-stone-600">
                        Bei Fragen zum Datenschutz oder zur Löschung deiner Daten wende dich an:
                        <a href="mailto:datenschutz@waldritter.de" class="text-waldritter underline hover:opacity-75">datenschutz@waldritter.de</a>
                    </p>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white/60 border-2 border-[#5a3a22]/40 rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
