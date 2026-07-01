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
                    <p class="text-sm text-stone-600 mb-6">
                        Steuere, welche E-Mails wir dir schicken. Aktiviere einen Schalter, um die jeweilige E-Mail zu
                        erhalten. Benachrichtigungen im Portal (Glocke oben rechts) bleiben davon unberührt.
                    </p>

                    <form method="POST" action="{{ route('profile.update') }}" class="ui form">
                        @csrf @method('PATCH')
                        <input type="hidden" name="name"     value="{{ $user->name }}">
                        <input type="hidden" name="lastname" value="{{ $user->lastname }}">
                        <input type="hidden" name="email"    value="{{ $user->email }}">
                        <input type="hidden" name="phone"    value="{{ $user->phone }}">

                        <div class="space-y-8">

                            {{-- Meine Anmeldungen (für alle Nutzer) --}}
                            <fieldset class="border-0 p-0 m-0">
                                <legend class="flex items-center gap-2 text-sm font-semibold text-waldritter uppercase tracking-wide mb-1 w-full">
                                    <i class="calendar check icon" aria-hidden="true"></i>
                                    <span>Meine Anmeldungen</span>
                                </legend>
                                <p class="text-xs text-stone-500 mb-3">Rund um deine eigenen Anmeldungen zu Abenteuern.</p>
                                <div class="divide-y divide-stone-200 border-t border-b border-stone-200">
                                    @foreach ([
                                        ['notify_booking_received',  'Anmeldung eingegangen',         'Du bekommst eine Bestätigung, sobald deine Anmeldung bei uns eingeht.'],
                                        ['notify_booking_approved',  'Anmeldung bestätigt',            'Wir sagen dir Bescheid, wenn dein Platz beim Abenteuer bestätigt ist.'],
                                        ['notify_booking_rejected',  'Anmeldung abgelehnt',            'Du wirst informiert, falls deine Anmeldung nicht angenommen werden konnte.'],
                                        ['notify_booking_cancelled', 'Stornierung bestätigt',          'Du erhältst eine Bestätigung, wenn du eine Anmeldung stornierst.'],
                                        ['notify_payment_confirmed', 'Zahlung eingegangen',            'Wir bestätigen dir, wenn deine Zahlung bei uns angekommen ist.'],
                                        ['notify_waitlist_promoted', 'Von der Warteliste nachgerückt', 'Du erfährst sofort, wenn für dich ein Platz frei geworden ist.'],
                                        ['notify_event_cancelled',   'Abenteuer abgesagt',             'Wir benachrichtigen dich, falls ein Abenteuer nicht stattfinden kann.'],
                                        ['notify_event_reminder',    'Erinnerung vor dem Abenteuer',   'Du bekommst kurz vorher eine Erinnerung, damit du nichts verpasst.'],
                                    ] as [$col, $title, $desc])
                                        <div class="py-4">
                                            <div class="ui toggle checkbox">
                                                <input type="checkbox" name="{{ $col }}" id="{{ $col }}" value="1"
                                                       @checked($user->$col ?? true) aria-describedby="{{ $col }}_desc">
                                                <label for="{{ $col }}" class="text-stone-800 font-medium">{{ $title }}</label>
                                            </div>
                                            <p id="{{ $col }}_desc" class="text-sm text-stone-500 leading-snug mt-1 ml-[3.75rem]">{{ $desc }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </fieldset>

                            @if ($user->hasAnyRole('teamer', 'lehrmeister'))
                                <fieldset class="border-0 p-0 m-0">
                                    <legend class="flex items-center gap-2 text-sm font-semibold text-waldritter uppercase tracking-wide mb-1 w-full">
                                        <i class="users icon" aria-hidden="true"></i>
                                        <span>Teamer &amp; Lehrmeister</span>
                                    </legend>
                                    <p class="text-xs text-stone-500 mb-3">Nur für dich sichtbar, weil du im Team mithilfst.</p>
                                    <div class="divide-y divide-stone-200 border-t border-b border-stone-200">
                                        <div class="py-4">
                                            <div class="ui toggle checkbox">
                                                <input type="checkbox" name="teamer_notifications" id="teamer_notifications" value="1"
                                                       @checked($user->teamer_notifications ?? true) aria-describedby="teamer_notifications_desc">
                                                <label for="teamer_notifications" class="text-stone-800 font-medium">Teamer-Einladungen</label>
                                            </div>
                                            <p id="teamer_notifications_desc" class="text-sm text-stone-500 leading-snug mt-1 ml-[3.75rem]">Du wirst per E-Mail eingeladen, wenn Teamer für ein Abenteuer gesucht werden.</p>
                                        </div>
                                    </div>
                                </fieldset>
                            @endif

                            @if ($user->hasRole('project_lead'))
                                <fieldset class="border-0 p-0 m-0">
                                    <legend class="flex items-center gap-2 text-sm font-semibold text-waldritter uppercase tracking-wide mb-1 w-full">
                                        <i class="clipboard list icon" aria-hidden="true"></i>
                                        <span>Projektleitung</span>
                                    </legend>
                                    <p class="text-xs text-stone-500 mb-3">Nur für dich sichtbar, weil du ein Abenteuer leitest.</p>
                                    <div class="divide-y divide-stone-200 border-t border-b border-stone-200">
                                        <div class="py-4">
                                            <div class="ui toggle checkbox">
                                                <input type="checkbox" name="notify_cancellation_report" id="notify_cancellation_report" value="1"
                                                       @checked($user->notify_cancellation_report ?? true) aria-describedby="notify_cancellation_report_desc">
                                                <label for="notify_cancellation_report" class="text-stone-800 font-medium">Stornierungen von Teilnehmern</label>
                                            </div>
                                            <p id="notify_cancellation_report_desc" class="text-sm text-stone-500 leading-snug mt-1 ml-[3.75rem]">Du wirst informiert, wenn sich jemand von deinem Abenteuer abmeldet.</p>
                                        </div>
                                    </div>
                                </fieldset>
                            @endif

                            @if ($user->hasRole('admin'))
                                <fieldset class="border-0 p-0 m-0">
                                    <legend class="flex items-center gap-2 text-sm font-semibold text-waldritter uppercase tracking-wide mb-1 w-full">
                                        <i class="shield alternate icon" aria-hidden="true"></i>
                                        <span>Administration</span>
                                    </legend>
                                    <p class="text-xs text-stone-500 mb-3">Nur für dich sichtbar, weil du das Portal verwaltest.</p>
                                    <div class="divide-y divide-stone-200 border-t border-b border-stone-200">
                                        <div class="py-4">
                                            <div class="ui toggle checkbox">
                                                <input type="checkbox" name="notify_new_user" id="notify_new_user" value="1"
                                                       @checked($user->notify_new_user ?? true) aria-describedby="notify_new_user_desc">
                                                <label for="notify_new_user" class="text-stone-800 font-medium">Neue Nutzer-Registrierung</label>
                                            </div>
                                            <p id="notify_new_user_desc" class="text-sm text-stone-500 leading-snug mt-1 ml-[3.75rem]">Du erhältst eine E-Mail, sobald sich eine neue Person im Portal registriert.</p>
                                        </div>
                                    </div>
                                </fieldset>
                            @endif

                        </div>

                        <button type="submit" class="ui primary button mt-6">Einstellungen speichern</button>
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

    {{-- Fomantic-Toggle-Schalter der Benachrichtigungs-Sektion initialisieren
         (auf normal gerenderten Seiten laeuft sonst keine .checkbox()-Init). --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (window.jQuery) {
                    window.jQuery('.ui.toggle.checkbox').checkbox();
                }
            });
        </script>
    @endpush
</x-app-layout>
