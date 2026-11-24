<x-app-layout>
    @php
        $stunde = (int) now()->format('H');
        $gruss = $stunde >= 6 && $stunde < 10 ? 'Guten Morgen' : ($stunde >= 10 && $stunde < 18 ? 'Guten Tag' : 'Guten Abend');
    @endphp

    {{-- Waldritter-Designsystem v1 (Pilot): neue Tokens gelten nur innerhalb von .wr-v1 --}}
    <div class="wr-v1 py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Begrüßung --}}
            <div class="mb-6 sm:mb-8">
                <span class="wr-eyebrow">Deine Abenteuerwelt</span>
                <h1 id="dashboard-heading" tabindex="-1" class="wr-heading focus:outline-none">{{ $gruss }}, {{ Auth::user()->name }}</h1>

                {{-- Willkommenstext nur ohne Startseiten-Nachricht; bei wegklickbarer MOTD erscheint er nach dem Schließen --}}
                @if (! $motd || $motd->dismissible)
                    <p @if ($motd)
                           x-data="{
                               zeigen: false,
                               init() { try { this.zeigen = !!localStorage.getItem('{{ $motd->dismiss_key }}'); } catch (e) {} }
                           }"
                           x-show="zeigen" x-cloak @motd-dismissed.window="zeigen = true"
                       @endif
                       class="wr-intro">
                        Willkommen im Heldenregister. Hier findest du alles rund um deine Spieler, Helden und Abenteuer.
                    </p>
                @endif
            </div>

            @if (session('newsletter_status') === 'confirmation_sent')
                <div class="ui success message mb-4">
                    <i class="envelope icon"></i>
                    <strong>Fast geschafft!</strong> Wir haben dir eine Bestätigungs-E-Mail geschickt. Bitte klicke auf den Link darin, um dein Abo zu aktivieren.
                </div>
            @elseif (session('newsletter_status') === 'already_subscribed')
                <div class="ui info message mb-4">
                    <i class="info circle icon"></i>
                    Du bist bereits für den Newsletter angemeldet.
                </div>
            @endif

            @include('dashboard._motd')

            {{-- Onboarding-Fortschrittsbox: solange nicht alle drei Aufgaben erledigt sind --}}
            @if ($showOnboarding)
                {{-- „Ausblenden“ wird pro Konto im Browser gemerkt (geteilte Geräte von Eltern und Kindern). --}}
                <section aria-labelledby="onboarding-heading"
                         x-data="{
                             elternOffen: false,
                             key: 'onboarding_dismissed_{{ Auth::id() }}',
                             sichtbar: true,
                             init() { try { this.sichtbar = !localStorage.getItem(this.key); } catch (e) {} },
                             ausblenden() {
                                 try { localStorage.setItem(this.key, '1'); } catch (e) {}
                                 this.sichtbar = false;
                                 this.$nextTick(() => document.getElementById('dashboard-heading')?.focus());
                             }
                         }"
                         x-show="sichtbar" x-cloak
                         class="wr-card p-4 sm:p-6 mb-6 sm:mb-8">

                    <div class="flex items-start justify-between gap-3 mb-1">
                        <h2 id="onboarding-heading" class="wr-section-heading__title">Deine ersten Schritte</h2>
                        <button type="button" @click="ausblenden()"
                                class="wr-btn wr-btn--secondary wr-btn--small shrink-0">
                            <i class="eye slash icon m-0" aria-hidden="true"></i>
                            Ausblenden
                        </button>
                    </div>
                    <p class="wr-card__text mb-5">
                        Herzlich willkommen bei den Waldrittern! Erledige diese drei Aufgaben, um loszulegen.
                    </p>

                    <ol role="list" class="space-y-5 mb-5 p-0 list-none">
                        <x-wr.step :number="1" title="Profil vervollständigen" :done="$profileComplete">
                            <p class="m-0">Trage deine Adresse und Telefonnummer ein – diese werden für die Anmeldung zu Veranstaltungen benötigt.</p>
                            <a href="{{ route('profile.edit') }}" class="wr-btn wr-btn--primary wr-btn--small">Zum Profil</a>
                        </x-wr.step>

                        <x-wr.step :number="2" title="Spieler anlegen" :done="$hasPlayers"
                                   :later-hint="! $profileComplete && ! $hasPlayers ? 'nach Schritt 1' : null">
                            <p class="m-0">Lege deinen Spieler (oder dein Kind) an – das geht direkt hier im Portal.</p>
                            <a href="{{ route('players.create') }}"
                               class="wr-btn wr-btn--small {{ $profileComplete ? 'wr-btn--primary' : 'wr-btn--secondary' }}">
                                Spieler anlegen
                            </a>
                            <p class="text-xs m-0">
                                Existiert ein Spieler bereits über ein anderes Elternteil?
                                Schreibe uns bitte eine E-Mail an
                                <a href="mailto:{{ config('portal.contact_email') }}"
                                   class="wr-interactive underline text-wr-ink">{{ config('portal.contact_email') }}</a>,
                                damit wir den Spieler deinem Konto zuordnen können.
                            </p>
                        </x-wr.step>

                        <x-wr.step :number="3" title="Zu einer Veranstaltung anmelden" :done="$hasBookings"
                                   :later-hint="! $hasPlayers && ! $hasBookings ? 'nach Schritt 2' : null">
                            <p class="m-0">
                                Such dir ein Abenteuer aus und melde deinen Spieler an. Deinen Helden erstellst du dann direkt vor Ort
                                beim <strong class="font-medium">Bürokraten</strong> – das ist die Person der Waldritter, die die Helden ins Heldenregister einträgt.
                            </p>
                            @can('adventure.access')
                                <a href="{{ route('adventures.index') }}"
                                   class="wr-btn wr-btn--small {{ $hasPlayers ? 'wr-btn--primary' : 'wr-btn--secondary' }}">
                                    Abenteuer ansehen
                                </a>
                            @endcan
                        </x-wr.step>
                    </ol>

                    <div class="pt-3 border-t border-wr-border">
                        <button type="button"
                                x-on:click="elternOffen = !elternOffen"
                                :aria-expanded="elternOffen.toString()"
                                aria-controls="onboarding-eltern"
                                class="wr-interactive inline-flex items-center gap-1 min-h-[44px] text-sm underline text-wr-ink-muted hover:text-wr-ink">
                            <i class="info circle icon" aria-hidden="true"></i>
                            Infos für Eltern &amp; Erziehungsberechtigte
                        </button>
                        <div id="onboarding-eltern"
                             x-show="elternOffen"
                             x-cloak
                             class="mt-2 text-sm text-wr-ink-muted space-y-2">
                            <p>Lege zunächst dein Kind als Spieler an und melde es zu einer Veranstaltung an. Auf der Veranstaltung erstellt die Spielleitung gemeinsam mit deinem Kind den Charakter (Helden). Eingetragen wird er vom Bürokraten: einer Person der Waldritter, die im Spiel für das Heldenregister zuständig ist. Erst nach der ersten Veranstaltung erscheint der Held hier im Portal.</p>
                            <p>Für Kinder unter 18 Jahren ist die Anmeldung nur mit deiner Einwilligung als erziehungsberechtigte Person möglich.</p>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Abenteuer: eigene Anmeldungen oder – falls keine – das nächste Abenteuer (UI-43) --}}
            @can('adventure.access')
                @if ($upcomingBookedAdventures->isNotEmpty())
                    @php
                        // Das nächste gebuchte Abenteuer wird zum Quest-Banner mit Countdown, weitere bleiben Karten.
                        $naechstesGebucht = $upcomingBookedAdventures->first();
                        $tageBis = (int) now()->startOfDay()->diffInDays($naechstesGebucht->start_at->copy()->startOfDay());
                        $countdown = match (true) {
                            $tageBis <= 0 => 'Heute geht es los!',
                            $tageBis === 1 => 'Morgen geht es los!',
                            default => 'Noch ' . $tageBis . ' Tage bis zum Abenteuer!',
                        };
                    @endphp
                    <section aria-labelledby="bookings-heading">
                        <x-wr.section-heading title="Meine Anmeldungen" id="bookings-heading" class="mt-0" />

                        <x-wr.quest-banner :title="$naechstesGebucht->name"
                                           :eyebrow="$countdown"
                                           heading-id="booked-adventure-heading"
                                           heading-level="h3">
                            <x-wr.adventure-meta :adventure="$naechstesGebucht" class="wr-quest__meta" />
                            <x-wr.booking-participants :adventure="$naechstesGebucht" class="mb-4" />
                            <div>
                                <a href="{{ route('adventures.show', $naechstesGebucht) }}" class="wr-btn wr-btn--on-dark">
                                    Details ansehen
                                    <i class="arrow right icon" aria-hidden="true"></i>
                                </a>
                            </div>
                        </x-wr.quest-banner>

                        @if ($upcomingBookedAdventures->count() > 1)
                            <h3 class="font-semibold text-base mt-5 mb-2">Weitere Anmeldungen</h3>
                            <div class="space-y-3">
                                @foreach ($upcomingBookedAdventures->skip(1) as $adventure)
                                    <x-wr.booking-card :adventure="$adventure" />
                                @endforeach
                            </div>
                        @endif
                    </section>
                @elseif ($nextAdventure)
                    <x-wr.quest-banner :title="$nextAdventure->name"
                                       eyebrow="Das nächste Kapitel wartet"
                                       heading-id="next-adventure-heading">
                        <x-wr.adventure-meta :adventure="$nextAdventure" class="wr-quest__meta" />
                        <div class="wr-status-chip mb-4">
                            @include('dashboard._adventure_status', ['slots' => $nextAdventureSlots])
                        </div>
                        <div>
                            <a href="{{ route('adventures.show', $nextAdventure) }}" class="wr-btn wr-btn--on-dark">
                                {{ $nextAdventure->registrationOpen() ? 'Jetzt anmelden' : 'Details ansehen' }}
                                <i class="arrow right icon" aria-hidden="true"></i>
                            </a>
                        </div>
                    </x-wr.quest-banner>
                @endif
            @endcan

            {{-- Issue #19: abrechenbare Teamer-Anmeldungen --}}
            @include('dashboard._teamer_compensations')

            {{-- Aktive Helden der eigenen Spieler --}}
            @if ($activePlayers->isNotEmpty() || $playersAwaitingHero->isNotEmpty())
                <section aria-labelledby="heroes-heading">
                    <x-wr.section-heading title="Deine Helden" id="heroes-heading" />
                    <div class="grid gap-4 lg:grid-cols-2">
                        @foreach ($activePlayers as $activePlayer)
                            @if ($activePlayer->activeHero)
                                <x-wr.hero-card :hero="$activePlayer->activeHero"
                                                :label="$activePlayer->pivot->self ? 'Mein aktiver Held' : 'Held von ' . $activePlayer->name"
                                                :skills-count="$activePlayer->activeHero->skills_count"
                                                :adventures-count="$activePlayer->visits_count" />
                            @endif
                        @endforeach

                        {{-- Eigene Spieler ohne Helden: Platzhalter statt Leere --}}
                        @foreach ($playersAwaitingHero as $waitingPlayer)
                            <x-wr.hero-placeholder :player="$waitingPlayer" :self="(bool) $waitingPlayer->pivot->self" />
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Kachel-Navigation (alle Bildschirmgrößen) --}}
            <section aria-labelledby="world-heading">
                <x-wr.section-heading title="Wohin möchtest du?" id="world-heading" focusable />
                <div class="grid gap-3 grid-cols-2 sm:gap-5 lg:grid-cols-3">
                    <x-wr.card compact :href="route('profile.edit')" image="/images/dein_profil.jpg"
                               tag="Persönlich" title="Dein Profil" text="Kontaktdaten und Einstellungen" />

                    @can('heldenregister.view')
                        <x-wr.card compact :href="route('heroes.index')" image="/images/heroes_db_v2.jpg"
                                   tag="Helden" title="Heldenverwaltung" text="Auflistung aller Helden" />
                    @endcan

                    @can('adventure.access')
                        <x-wr.card compact :href="route('adventures.index')" image="/images/abenteuer_v3.jpg"
                                   tag="Veranstaltungen" title="Abenteuer" text="Entdecke Abenteuer und melde dich an" />
                    @endcan

                    <x-wr.card compact :href="route('players.index')" image="/images/spieler_verwaltung2.jpg"
                               tag="Spielerdatenbank" title="Deine Spieler" text="Spieler anlegen und verwalten" />

                    @can('portal.manage')
                        <x-wr.card compact :href="route('admin.index')" image="/images/verwaltung.jpg"
                                   tag="Portal-Administration" title="Verwaltung" text="Stammdaten, Einstellungen und Auswertungen" />
                    @endcan

                    {{-- Issue #30: Öffentliches Heldenverzeichnis (Helden und bekannte Plot-Charaktere) --}}
                    <x-wr.card compact :href="route('public.hero.search')" image="/images/heldenarchiv.jpg"
                               tag="Verzeichnis" title="Heldenverzeichnis" text="Helden und bekannte Gestalten erforschen" />

                    <x-wr.card compact href="https://www.waldritter-giessen.de/brans-tagebuch/" hint="auf der Waldritter-Website" image="/images/brans_tagebuch_hr.jpg"
                               tag="Chronik" title="Brans Tagebuch" text="Erzählungen aus den Abenteuern" />
                </div>
            </section>

            {{-- Newsletter-Hinweis für Nicht-Abonnenten (Panoramabanner mit Hintergrundbild).
                 Bewusst hinter den Kacheln: erst die eigenen Aufgaben, dann Werbung.
                 Inline-Hintergrund bewusst beibehalten: der Datensparmodus blendet [style*="background-image"] aus. --}}
            @if ($showNewsletterHint)
                <div x-data="{
                         sichtbar: !localStorage.getItem('newsletter_banner_dismissed'),
                         schliessen() {
                             localStorage.setItem('newsletter_banner_dismissed', '1');
                             this.sichtbar = false;
                             {{-- Fokus nicht an den Seitenanfang verlieren --}}
                             this.$nextTick(() => document.getElementById('world-heading')?.focus());
                         }
                     }"
                     x-show="sichtbar" x-cloak
                     class="relative overflow-hidden rounded-[14px] mt-8 border border-wr-border bg-wr-parchment"
                     style="background-image: url('/images/newsletter_banner.jpg'); background-size: cover; background-position: center; min-height: 120px;">

                    {{-- Inhalt direkt auf dem hellen Pergamentbild (kein dunkles Overlay, siehe Issue #12) --}}
                    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 p-4 sm:p-5">
                        <div class="flex-1 bg-wr-paper border border-wr-border rounded-lg px-4 py-2 shadow-sm">
                            <p class="text-sm leading-snug m-0">
                                <strong class="font-heading text-base block mb-0.5">Kein Newsletter?</strong>
                                Verpasse keine Neuigkeiten zu Abenteuern und Ankündigungen der Waldritter.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2 shrink-0">
                            <form method="POST" action="{{ route('newsletter.subscribe') }}">
                                @csrf
                                <button type="submit" class="wr-btn wr-btn--primary wr-btn--small">
                                    <i class="envelope icon" aria-hidden="true"></i> Jetzt abonnieren
                                </button>
                            </form>
                            <button type="button" @click="schliessen()" class="wr-btn wr-btn--secondary wr-btn--small">
                                Nein danke
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Admin-Kennzahlen (REP-06): ruhiger Verwaltungsabschnitt am Ende --}}
            @if (! empty($metrics))
                <section aria-labelledby="metrics-heading">
                    <x-wr.section-heading title="Verwaltung auf einen Blick" id="metrics-heading" />
                    <div class="grid gap-3 sm:gap-4 grid-cols-2 lg:grid-cols-4">
                        <x-wr.stat-tile label="Spieler" :value="$metrics['players']" icon="users" />
                        <x-wr.stat-tile label="Helden" :value="$metrics['heroes']" icon="chess knight" />
                        <x-wr.stat-tile label="Kommende Abenteuer" :value="$metrics['upcoming_events']" icon="calendar alternate" />
                        <x-wr.stat-tile label="Unbestätigte Anmeldungen" :value="$metrics['open_bookings']" icon="clipboard list" />
                    </div>
                </section>
            @endif

        </div>
    </div>
</x-app-layout>
