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

            {{-- AUTH-13: E-Mail-Benachrichtigungen je Rolle --}}
            @php($user = auth()->user())
            <div class="p-4 sm:p-8 bg-white/60 border-2 border-[#5a3a22]/40 rounded-lg">
                <div class="max-w-xl">
                    <h2 class="font-uncial text-lg text-waldritter mb-1">E-Mail-Benachrichtigungen</h2>
                    <p class="text-sm text-stone-600 mb-4">Steuere, welche E-Mails du erhältst. Portal-Benachrichtigungen (Glocke) bleiben davon unberührt.</p>

                    <form method="POST" action="{{ route('profile.update') }}" class="ui form">
                        @csrf @method('PATCH')
                        <input type="hidden" name="name"     value="{{ $user->name }}">
                        <input type="hidden" name="lastname" value="{{ $user->lastname }}">
                        <input type="hidden" name="email"    value="{{ $user->email }}">
                        <input type="hidden" name="phone"    value="{{ $user->phone }}">

                        <div class="ui segments">

                            {{-- Meine Anmeldungen (für alle Nutzer) --}}
                            <div class="ui segment">
                                <div class="text-xs font-semibold text-stone-400 uppercase tracking-wide mb-3">
                                    <i class="calendar check icon"></i> Meine Anmeldungen
                                </div>
                                <div class="space-y-3">
                                    @foreach ([
                                        'notify_booking_received'  => 'Buchungseingang (Anmeldebestätigung)',
                                        'notify_booking_approved'  => 'Anmeldung bestätigt',
                                        'notify_booking_rejected'  => 'Anmeldung abgelehnt',
                                        'notify_booking_cancelled' => 'Stornierungsbestätigung',
                                        'notify_payment_confirmed' => 'Zahlungseingang bestätigt',
                                        'notify_waitlist_promoted' => 'Von der Warteliste nachgerückt',
                                        'notify_event_cancelled'   => 'Event abgesagt',
                                        'notify_event_reminder'    => 'Erinnerung vor dem Event',
                                    ] as $col => $label)
                                        <div class="ui toggle checkbox">
                                            <input type="checkbox" name="{{ $col }}" id="{{ $col }}" value="1"
                                                   @checked($user->$col ?? true)>
                                            <label for="{{ $col }}" class="text-stone-700">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if ($user->hasAnyRole('teamer', 'lehrmeister'))
                                <div class="ui segment">
                                    <div class="text-xs font-semibold text-stone-400 uppercase tracking-wide mb-3">
                                        <i class="users icon"></i> Teamer &amp; Lehrmeister
                                    </div>
                                    <div class="ui toggle checkbox">
                                        <input type="checkbox" name="teamer_notifications" id="teamer_notifications" value="1"
                                               @checked($user->teamer_notifications ?? true)>
                                        <label for="teamer_notifications" class="text-stone-700">
                                            Teamer-Einladungen per E-Mail erhalten
                                        </label>
                                    </div>
                                </div>
                            @endif

                            @if ($user->hasRole('project_lead'))
                                <div class="ui segment">
                                    <div class="text-xs font-semibold text-stone-400 uppercase tracking-wide mb-3">
                                        <i class="clipboard list icon"></i> Projektleitung
                                    </div>
                                    <div class="ui toggle checkbox">
                                        <input type="checkbox" name="notify_cancellation_report" id="notify_cancellation_report" value="1"
                                               @checked($user->notify_cancellation_report ?? true)>
                                        <label for="notify_cancellation_report" class="text-stone-700">
                                            Stornierungen von Teilnehmern per E-Mail erhalten
                                        </label>
                                    </div>
                                </div>
                            @endif

                            @if ($user->hasRole('admin'))
                                <div class="ui segment">
                                    <div class="text-xs font-semibold text-stone-400 uppercase tracking-wide mb-3">
                                        <i class="shield alternate icon"></i> Administration
                                    </div>
                                    <div class="ui toggle checkbox">
                                        <input type="checkbox" name="notify_new_user" id="notify_new_user" value="1"
                                               @checked($user->notify_new_user ?? true)>
                                        <label for="notify_new_user" class="text-stone-700">
                                            Neue Nutzer-Registrierung per E-Mail erhalten
                                        </label>
                                    </div>
                                </div>
                            @endif

                        </div>

                        <button type="submit" class="ui primary button mt-4">Einstellungen speichern</button>
                    </form>
                </div>
            </div>

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
