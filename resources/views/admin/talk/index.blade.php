{{-- Issue #31 (Phase 1): Nextcloud-Talk-Anbindung – Status, Verbindungstest, Einstellungen --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Nextcloud Talk</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl lg:max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-3 bg-green-100 text-green-800 rounded" role="status">{{ session('status') }}</div>
            @endif

            <p class="text-sm">
                <a href="{{ route('admin.talk.logs.index') }}" class="underline"><i class="list icon" aria-hidden="true"></i>Talk-Protokoll</a>
                – alle Änderungen in Nextcloud und alle Fehler
            </p>

            {{-- Desktop: Verbindung und Einstellungen nebeneinander --}}
            <div class="grid gap-6 lg:grid-cols-2 items-start">

            {{-- Verbindung (Zugangsdaten aus der .env) --}}
            <section aria-labelledby="talk-connection" class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                <h3 id="talk-connection" class="font-semibold text-waldritter text-lg mb-3">Verbindung</h3>

                @if ($configured)
                    <p class="mb-1"><span class="ui green label">Konfiguriert</span></p>
                    <p class="text-sm text-stone-700 mb-4">Adresse: <code>{{ $url }}</code></p>
                @else
                    <p class="mb-1"><span class="ui grey label">Nicht konfiguriert</span></p>
                    <p class="text-sm text-stone-700 mb-4">
                        Adresse und Zugang werden in der <code>.env</code> eingetragen:
                        <code>NEXTCLOUD_URL</code>, <code>NEXTCLOUD_USER</code> und <code>NEXTCLOUD_APP_PASSWORD</code>
                        (App-Passwort eines technischen Kontos). Ohne diese Angaben bleiben alle Talk-Funktionen aus.
                    </p>
                @endif

                @if ($result = session('talk_test'))
                    <div class="ui {{ $result['ok'] ? 'positive' : 'negative' }} message" role="status">
                        <p class="m-0 font-semibold">{{ $result['message'] }}</p>
                        @if (! empty($result['user']))
                            <ul class="list m-0 mt-2 text-sm">
                                <li>Angemeldet als: {{ $result['user'] }}</li>
                                @if (! empty($result['version']))
                                    <li>Nextcloud-Version: {{ $result['version'] }}</li>
                                @endif
                                <li>Talk-Version: {{ $result['talk_version'] ?? 'nicht installiert' }}</li>
                                @foreach ($result['groups'] ?? [] as $groupCheck)
                                    <li>
                                        <i class="{{ $groupCheck['ok'] ? 'check circle' : 'exclamation triangle' }} icon" aria-hidden="true"></i>
                                        {{ $groupCheck['text'] }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                <details class="text-sm text-stone-700 mb-4">
                    <summary class="cursor-pointer font-medium">Rechte des technischen Kontos in Nextcloud</summary>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li><strong>Kein Admin</strong>, sondern <strong>Gruppen-Admin</strong> der beiden Talk-Gruppen (z. B. TALK-User und TALK-U18).
                            So kann es Konten anlegen und sperren, aber niemandem Admin-Rechte geben.</li>
                        <li><strong>Mitglied</strong> der Gruppe für volljährige Talk-Nutzer, falls nur diese Gruppe Unterhaltungen anlegen darf (für die Teamer-Räume).</li>
                        <li><strong>Moderator</strong> im Plot-Team-Chat (Plot-Threads anlegen und umbenennen).</li>
                        <li>Hinweis: Als Gruppen-Admin findet das Konto vorhandene Nextcloud-Konten nur in seinen Gruppen.
                            Teamer mit eigenem Konto einmal in die Volljährigen-Gruppe aufnehmen oder den Benutzernamen im Nutzerprofil eintragen.</li>
                    </ul>
                </details>

                <form method="POST" action="{{ route('admin.talk.test') }}">
                    @csrf
                    <button type="submit" class="ui secondary button" @disabled(! $configured)>
                        <i class="plug icon" aria-hidden="true"></i> Verbindung testen
                    </button>
                </form>
            </section>

            {{-- Einstellungen --}}
            <section aria-labelledby="talk-settings" class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                <h3 id="talk-settings" class="font-semibold text-waldritter text-lg mb-3">Einstellungen</h3>

                <form method="POST" action="{{ route('admin.talk.update') }}" class="ui form space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="field">
                        <div class="ui checkbox">
                            <input type="checkbox" id="talk_enabled" name="talk_enabled" value="1"
                                   @checked(old('talk_enabled', ($values['talk_enabled'] ?? '0') === '1'))>
                            <label for="talk_enabled">Talk-Integration aktiv</label>
                        </div>
                        <p class="text-xs text-stone-500 mt-1">Hauptschalter für Event- und Spieler-Funktionen (wirkt nur, wenn die Verbindung konfiguriert ist).</p>
                    </div>

                    <div class="field">
                        <label for="talk_plot_room">Plot-Team-Chat</label>
                        <input type="text" id="talk_plot_room" name="talk_plot_room" maxlength="255"
                               value="{{ old('talk_plot_room', $values['talk_plot_room_token'] ?? '') }}"
                               placeholder="Talk-Link (…/index.php/call/abc123) oder Raum-Token">
                        <p class="text-xs text-stone-500 mt-1">In diesem Raum werden später die Plot-Threads zu neuen Events angelegt. Gespeichert wird nur der Raum-Token.</p>
                        <x-input-error :messages="$errors->get('talk_plot_room')" class="mt-1" />
                    </div>

                    <div class="field">
                        <label for="talk_user_group">Nextcloud-Gruppe für volljährige Talk-Nutzer</label>
                        <input type="text" id="talk_user_group" name="talk_user_group" maxlength="64"
                               value="{{ old('talk_user_group', $values['talk_user_group'] ?? '') }}" placeholder="z. B. TALK-User">
                        <x-input-error :messages="$errors->get('talk_user_group')" class="mt-1" />
                    </div>

                    <div class="field">
                        <label for="talk_u18_group">Nextcloud-Gruppe für U18-Konten</label>
                        <input type="text" id="talk_u18_group" name="talk_u18_group" maxlength="64"
                               value="{{ old('talk_u18_group', $values['talk_u18_group'] ?? '') }}" placeholder="z. B. TALK-U18">
                        <x-input-error :messages="$errors->get('talk_u18_group')" class="mt-1" />
                        <p class="text-xs text-stone-500 mt-1">
                            Gruppen-ID aus Nextcloud eintragen (Benutzer → Gruppe anklicken → ID in der Adresszeile).
                            Aus Sicherheitsgründen sind nur Gruppen erlaubt, die mit „{{ \App\Services\NextcloudTalk\NextcloudTalkClient::GROUP_PREFIX }}“ beginnen.
                            Am 18. Geburtstag wechselt das Konto automatisch von der U18- in die Volljährigen-Gruppe.
                        </p>
                    </div>

                    <div class="field">
                        <label for="talk_moderators">Moderatoren der Teamer-Räume</label>
                        <textarea id="talk_moderators" name="talk_moderators" rows="3" maxlength="2000"
                                  aria-describedby="talk_moderators_hint"
                                  placeholder="Nextcloud-Benutzernamen, einer pro Zeile">{{ old('talk_moderators', $values['talk_moderators'] ?? '') }}</textarea>
                        <p id="talk_moderators_hint" class="text-xs text-stone-500 mt-1">
                            Diese Personen werden in jeden Teamer-Raum aufgenommen und zu Moderatoren gemacht – so können z. B. andere
                            Nextcloud-Admins die Räume in Talk umbenennen, verwalten und löschen. Gilt für neue Räume sofort, für bestehende
                            beim nächsten Abgleich („Jetzt synchronisieren“). Wer aus der Liste entfernt wird, bleibt im Raum.
                        </p>
                        <x-input-error :messages="$errors->get('talk_moderators')" class="mt-1" />
                    </div>

                    <div class="field">
                        <label for="talk_app_url">Link zur Talk-App</label>
                        <input type="url" id="talk_app_url" name="talk_app_url" maxlength="255"
                               value="{{ old('talk_app_url', $values['talk_app_url'] ?? '') }}" placeholder="https://…/index.php/apps/spreed">
                        <x-input-error :messages="$errors->get('talk_app_url')" class="mt-1" />
                    </div>

                    <div class="field">
                        <label for="talk_help_url">Link zur Hilfe</label>
                        <input type="url" id="talk_help_url" name="talk_help_url" maxlength="255"
                               value="{{ old('talk_help_url', $values['talk_help_url'] ?? $defaultHelpUrl) }}">
                        <x-input-error :messages="$errors->get('talk_help_url')" class="mt-1" />
                    </div>

                    <div class="pt-2">
                        <x-primary-button>Speichern</x-primary-button>
                    </div>
                </form>
            </section>

            </div>

            {{-- Issue #31 (Phase 3): Texte für die Elternerlaubnis --}}
            <section aria-labelledby="talk-texts" class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">
                <h3 id="talk-texts" class="font-semibold text-waldritter text-lg mb-1">Texte für die Elternerlaubnis</h3>
                <p class="text-sm text-stone-600 mb-4">
                    Eltern minderjähriger Spieler lesen diese Texte im Reiter „Talk“ und stimmen dann zu.
                    Vorbelegt ist ein Entwurf – bitte vor dem Start anpassen. Bereits erteilte Erlaubnisse
                    behalten die Fassung, der zugestimmt wurde. Leeres Feld = Entwurf.
                </p>
                <form method="POST" action="{{ route('admin.talk.texts.update') }}" class="ui form space-y-5">
                    @csrf
                    @method('PUT')
                    @foreach (\App\Services\NextcloudTalk\TalkTexts::SECTIONS as $key => $label)
                        <div class="field">
                            <label for="{{ $key }}">{{ $label }}</label>
                            <textarea id="{{ $key }}" name="{{ $key }}" rows="6" maxlength="5000">{{ old($key, $texts[$key]) }}</textarea>
                            <x-input-error :messages="$errors->get($key)" class="mt-1" />
                        </div>
                    @endforeach
                    <div class="pt-2">
                        <x-primary-button>Texte speichern</x-primary-button>
                    </div>
                </form>
            </section>

        </div>
    </div>
</x-app-layout>
