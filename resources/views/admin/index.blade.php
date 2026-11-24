<x-app-layout>
    @php
        $adminCard = fn (string $title, string $subtitle, string $img, string $href) =>
            compact('title', 'subtitle', 'img', 'href');
    @endphp

    {{-- Waldritter-Designsystem v1: Verwaltungsübersicht (ruhig, Kacheln nach Bereichen) --}}
    <div class="wr-v1 py-6 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-6">
                <span class="wr-eyebrow">Heldenregister</span>
                <h1 class="wr-heading">Verwaltung</h1>
                @can('portal.manage')
                    <p class="wr-intro">Administration des Heldenregisters. Sei behutsam – lieber fragen als Versagen ;-)</p>
                @endcan
            </div>

            {{-- Helden & Spieler --}}
            @canany(['portal.manage', 'heroes.admin-access', 'groups.manage', 'id-cards.access'])
                <section aria-labelledby="admin-heroes" class="mb-8">
                    <h2 id="admin-heroes" class="wr-section-heading__title mb-4 flex items-center gap-2">
                        <i class="shield alternate icon m-0" aria-hidden="true"></i> Helden &amp; Spieler
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @can('portal.manage')
                            <x-admin.dashboard-card :card="$adminCard('Portal-Nutzer', 'Nutzer & Rollen', 'verwaltung_portal-nutzer.jpg', route('admin.users.index'))" />
                            <x-admin.dashboard-card :card="$adminCard('Spieler', 'Alle Spieler/Teilnehmer', 'verwaltung_spieler.jpg', route('admin.players.index'))" />
                        @endcan
                        @can('heroes.admin-access')
                            <x-admin.dashboard-card :card="$adminCard('Helden-Klassen', 'Klassen anlegen & pflegen', 'verwaltung_helden-klassen.jpg', route('admin.hero-classes.index'))" />
                            <x-admin.dashboard-card :card="$adminCard('Fertigkeiten', 'Fertigkeiten-Katalog pflegen', 'verwaltung_fertigkeiten.jpg', route('admin.skills.index'))" />
                            <x-admin.dashboard-card :card="$adminCard('Perlenfarben', 'Perlenfarben pflegen', 'verwaltung_perlenfarbe.jpg', route('admin.perl-colors.index'))" />
                            <x-admin.dashboard-card :card="$adminCard('EP-Buchungsarten', 'EP-Buchungsarten pflegen', 'verwaltung_ep-buchungsarten.jpg', route('admin.ep-transaction-types.index'))" />
                        @endcan
                        @can('groups.manage')
                            <x-admin.dashboard-card :card="$adminCard('Gruppen', 'Gilden & Trupps verwalten', 'verwaltung_helden-gruppen.jpg', route('admin.groups.index'))" />
                        @endcan
                        @can('id-cards.access')
                            <x-admin.dashboard-card :card="$adminCard('Heldenausweise', 'Ausweise generieren & zuweisen', 'verwaltung_heldenausweise.jpg', route('admin.id-cards.index'))" />
                        @endcan
                    </div>
                </section>
            @endcanany

            {{-- Plot & Geschichte (Issue #30) --}}
            @can('plot-characters.manage')
                <section aria-labelledby="admin-plot" class="mb-8">
                    <h2 id="admin-plot" class="wr-section-heading__title mb-4 flex items-center gap-2">
                        <i class="book icon m-0" aria-hidden="true"></i> Plot &amp; Geschichte
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <x-admin.dashboard-card :card="$adminCard('Plot-Charaktere', 'Bekannte Gestalten fürs Verzeichnis', 'heldenarchiv.jpg', route('admin.plot-characters.index'))" />
                    </div>
                </section>
            @endcan

            {{-- Veranstaltungen --}}
            @can('events.admin-access')
                <section aria-labelledby="admin-events" class="mb-8">
                    <h2 id="admin-events" class="wr-section-heading__title mb-4 flex items-center gap-2">
                        <i class="calendar alternate icon m-0" aria-hidden="true"></i> Veranstaltungen
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            $adminCard('Veranstaltungen', 'Abenteuer administrieren', 'verwaltung_veranstaltungen.jpg', route('adventures.manage-index')),
                            $adminCard('Orte', 'Veranstaltungsorte pflegen', 'verwaltung_orte.jpg', route('admin.locations.index')),
                            $adminCard('Kategorien', 'Event-Kategorien pflegen', 'verwaltung_event-kategorien.jpg', route('admin.event-categories.index')),
                            $adminCard('Auftraggeber', 'Auftraggeber pflegen', 'verwaltung_auftraggeber.jpg', route('admin.event-clients.index')),
                            $adminCard('Teilnahme-Rollen', 'Event-Rollen pflegen', 'verwaltung_teilnahme-rollen.jpg', route('admin.event-roles.index')),
                            $adminCard('Event-Status', 'Status-Lookups pflegen', 'verwaltung_event-status.jpg', route('admin.event-statuses.index')),
                            $adminCard('Taskmanager', 'Automatische Aufgaben konfigurieren', 'verwaltung_taskmanager.jpg', route('admin.task-manager.index')),
                        ] as $c)
                            <x-admin.dashboard-card :card="$c" />
                        @endforeach
                    </div>
                </section>
            @endcan

            {{-- System & Portal --}}
            @can('portal.manage')
                <section aria-labelledby="admin-system" class="mb-8">
                    <h2 id="admin-system" class="wr-section-heading__title mb-4 flex items-center gap-2">
                        <i class="cog icon m-0" aria-hidden="true"></i> System &amp; Portal
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            $adminCard('Rollen & Rechte', 'Berechtigungsübersicht', 'verwaltung_rollen-rechte.jpg', route('admin.roles.index')),
                            $adminCard('Portal-Einstellungen', 'Vereins-Settings bearbeiten', 'verwaltung_portal-einstellungen.jpg', route('admin.settings.index')),
                            $adminCard('Startseiten-Nachricht', 'Banner/Text auf der Startseite', 'verwaltung_portal-einstellungen.jpg', route('admin.motd.index')),
                            $adminCard('Audit-Log', 'Admin-Aktionen protokolliert', 'verwaltung_audit-log.jpg', route('admin.audit-logs.index')),
                            $adminCard('Datenpannen', 'DSGVO Art. 33 – Vorfallsprotokoll', 'verwaltung_audit-log.jpg', route('admin.data-breaches.index')),
                            $adminCard('Verarbeitungsverzeichnis', 'DSGVO Art. 30 – VVT', 'verwaltung_audit-log.jpg', route('admin.processing-activities.index')),
                        ] as $c)
                            <x-admin.dashboard-card :card="$c" />
                        @endforeach
                    </div>
                </section>
            @endcan

            {{-- Kommunikation: Newsletter, Nextcloud Talk (Issue #31) --}}
            @canany(['newsletter.manage', 'talk.manage'])
                <section aria-labelledby="admin-communication" class="mb-8">
                    <h2 id="admin-communication" class="wr-section-heading__title mb-4 flex items-center gap-2">
                        <i class="newspaper icon m-0" aria-hidden="true"></i> Kommunikation
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @can('newsletter.manage')
                            <x-admin.dashboard-card :card="$adminCard('Newsletter', 'Erstellen, bearbeiten & versenden', 'verwaltung_newsletter.jpg', route('admin.newsletter.index'))" />
                        @endcan
                        @can('talk.manage')
                            <x-admin.dashboard-card :card="$adminCard('Nextcloud Talk', 'Verbindung & Einstellungen', 'verwaltung.jpg', route('admin.talk.index'))" />
                        @endcan
                    </div>
                </section>
            @endcanany

            {{-- Feedback & Qualität --}}
            @canany(['survey.view', 'survey.admin'])
                <section aria-labelledby="admin-feedback" class="mb-8">
                    <h2 id="admin-feedback" class="wr-section-heading__title mb-4 flex items-center gap-2">
                        <i class="clipboard list icon m-0" aria-hidden="true"></i> Feedback &amp; Qualität
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <x-admin.dashboard-card :card="$adminCard('Umfragen', 'Ergebnisse & Übersicht', 'verwaltung_umfragen.jpg', route('admin.surveys.index'))" />
                        @can('survey.admin')
                            <x-admin.dashboard-card :card="$adminCard('Umfrage-Vorlagen', 'Fragenvorlagen anlegen & bearbeiten', 'verwaltung_umfragen-vorlagen.jpg', route('admin.surveys.templates.index'))" />
                        @endcan
                    </div>
                </section>
            @endcanany
        </div>
    </div>
</x-app-layout>
