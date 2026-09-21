<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Verwaltung</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @can('portal.manage')
            <div class="bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 mb-8 text-stone-700">
                Administration des Heldenregisters. Sei behutsam – lieber fragen als Versagen ;-)
            </div>
            @endcan

            @php
                $adminCard = fn(string $title, string $subtitle, string $img, string $href) =>
                    compact('title', 'subtitle', 'img', 'href');
            @endphp

            {{-- ── Helden & Spieler ───────────────────────────────────────────── --}}
            @canany(['portal.manage', 'heroes.admin-access', 'groups.manage', 'id-cards.access'])
            <div class="mb-10">
                <h3 class="font-uncial text-xl text-waldritter mb-4 flex items-center gap-2">
                    <i class="shield alternate icon" style="color:#5a3a22;"></i>
                    Helden & Spieler
                </h3>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @can('portal.manage')
                        @foreach ([
                            $adminCard('Portal-Nutzer', 'Nutzer & Rollen',         'verwaltung_portal-nutzer.jpg', route('admin.users.index')),
                            $adminCard('Spieler',       'Alle Spieler/Teilnehmer', 'verwaltung_spieler.jpg',       route('admin.players.index')),
                        ] as $c)
                            <x-admin.dashboard-card :card="$c" />
                        @endforeach
                    @endcan
                    @can('heroes.admin-access')
                        @foreach ([
                            $adminCard('Helden-Klassen',   'Klassen anlegen & pflegen',    'verwaltung_helden-klassen.jpg',   route('admin.hero-classes.index')),
                            $adminCard('Fertigkeiten',     'Fertigkeiten-Katalog pflegen', 'verwaltung_fertigkeiten.jpg',     route('admin.skills.index')),
                            $adminCard('Perlenfarben',     'Perlenfarben pflegen',         'verwaltung_perlenfarbe.jpg',      route('admin.perl-colors.index')),
                            $adminCard('EP-Buchungsarten', 'EP-Buchungsarten pflegen',     'verwaltung_ep-buchungsarten.jpg', route('admin.ep-transaction-types.index')),
                        ] as $c)
                            <x-admin.dashboard-card :card="$c" />
                        @endforeach
                    @endcan
                    @can('groups.manage')
                        <x-admin.dashboard-card :card="$adminCard('Gruppen', 'Gilden & Trupps verwalten', 'verwaltung_helden-gruppen.jpg', route('admin.groups.index'))" />
                    @endcan
                    @can('id-cards.access')
                        <x-admin.dashboard-card :card="$adminCard('Heldenausweise', 'Ausweise generieren & zuweisen', 'verwaltung_heldenausweise.jpg', route('admin.id-cards.index'))" />
                    @endcan
                </div>
            </div>
            @endcanany

            {{-- ── Veranstaltungen ────────────────────────────────────────────── --}}
            @can('events.admin-access')
            <div class="mb-10">
                <h3 class="font-uncial text-xl text-waldritter mb-4 flex items-center gap-2">
                    <i class="calendar alternate outline icon" style="color:#5a3a22;"></i>
                    Veranstaltungen
                </h3>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        $adminCard('Veranstaltungen',  'Abenteuer administrieren',   'verwaltung_veranstaltungen.jpg',  route('adventures.manage-index')),
                        $adminCard('Orte',             'Veranstaltungsorte pflegen', 'verwaltung_orte.jpg',             route('admin.locations.index')),
                        $adminCard('Kategorien',       'Event-Kategorien pflegen',   'verwaltung_event-kategorien.jpg', route('admin.event-categories.index')),
                        $adminCard('Auftraggeber',     'Auftraggeber pflegen',       'verwaltung_auftraggeber.jpg',     route('admin.event-clients.index')),
                        $adminCard('Teilnahme-Rollen', 'Event-Rollen pflegen',       'verwaltung_teilnahme-rollen.jpg', route('admin.event-roles.index')),
                        $adminCard('Event-Status',     'Status-Lookups pflegen',     'verwaltung_event-status.jpg',     route('admin.event-statuses.index')),
                        $adminCard('Taskmanager',      'Automatische Aufgaben konfigurieren', 'verwaltung_event-kategorien.jpg', route('admin.task-manager.index')),
                    ] as $c)
                        <x-admin.dashboard-card :card="$c" />
                    @endforeach
                </div>
            </div>
            @endcan

            {{-- ── System & Portal ────────────────────────────────────────────── --}}
            @can('portal.manage')
            <div class="mb-10">
                <h3 class="font-uncial text-xl text-waldritter mb-4 flex items-center gap-2">
                    <i class="cog icon" style="color:#5a3a22;"></i>
                    System & Portal
                </h3>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        $adminCard('Rollen & Rechte',          'Berechtigungsübersicht',            'verwaltung_rollen-rechte.jpg',        route('admin.roles.index')),
                        $adminCard('Portal-Einstellungen',     'Vereins-Settings bearbeiten',       'verwaltung_portal-einstellungen.jpg', route('admin.settings.index')),
                        $adminCard('Audit-Log',                'Admin-Aktionen protokolliert',      'verwaltung_audit-log.jpg',            route('admin.audit-logs.index')),
                        $adminCard('Datenpannen',              'DSGVO Art. 33 – Vorfallsprotokoll', 'verwaltung_audit-log.jpg',            route('admin.data-breaches.index')),
                        $adminCard('Verarbeitungsverzeichnis', 'DSGVO Art. 30 – VVT',              'verwaltung_audit-log.jpg',            route('admin.processing-activities.index')),
                    ] as $c)
                        <x-admin.dashboard-card :card="$c" />
                    @endforeach
                </div>
            </div>
            @endcan

            {{-- Kommunikation: Newsletter (newsletter.manage) --}}
            @can('newsletter.manage')
                <div class="mb-10">
                    <h3 class="font-uncial text-xl text-waldritter mb-4 flex items-center gap-2">
                        <i class="newspaper icon" style="color:#5a3a22;"></i>
                        Kommunikation
                    </h3>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <a href="{{ route('admin.newsletter.index') }}"
                           class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                            <div class="h-36 overflow-hidden save-data-hide">
                                <img src="/images/verwaltung_newsletter.jpg" alt="" aria-hidden="true" loading="lazy"
                                     width="400" height="144"
                                     class="w-full h-full object-cover group-hover:scale-105 transition">
                            </div>
                            <div class="p-3 text-center">
                                <div class="font-uncial text-base text-waldritter">Newsletter</div>
                                <div class="text-xs text-stone-600">Erstellen, bearbeiten &amp; versenden</div>
                            </div>
                        </a>
                    </div>
                </div>
            @endcan

            {{-- Feedback & Qualität: nur für Nutzer mit survey.view oder survey.admin --}}
            @canany(['survey.view', 'survey.admin'])
                <div class="mb-10">
                    <h3 class="font-uncial text-xl text-waldritter mb-4 flex items-center gap-2">
                        <i class="clipboard list icon" style="color:#5a3a22;"></i>
                        Feedback & Qualität
                    </h3>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <a href="{{ route('admin.surveys.index') }}"
                           class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                            <div class="h-36 overflow-hidden save-data-hide">
                                <img src="/images/verwaltung_umfragen.jpg" alt="" aria-hidden="true" loading="lazy"
                                     width="400" height="144"
                                     class="w-full h-full object-cover group-hover:scale-105 transition">
                            </div>
                            <div class="p-3 text-center">
                                <div class="font-uncial text-base text-waldritter">Umfragen</div>
                                <div class="text-xs text-stone-600">Ergebnisse & Übersicht</div>
                            </div>
                        </a>

                        @can('survey.admin')
                            <a href="{{ route('admin.surveys.templates.index') }}"
                               class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                                <div class="h-36 overflow-hidden save-data-hide">
                                    <img src="/images/verwaltung_umfragen-vorlagen.jpg" alt="" aria-hidden="true" loading="lazy"
                                         width="400" height="144"
                                         class="w-full h-full object-cover group-hover:scale-105 transition">
                                </div>
                                <div class="p-3 text-center">
                                    <div class="font-uncial text-base text-waldritter">Umfrage-Vorlagen</div>
                                    <div class="text-xs text-stone-600">Fragenvorlagen anlegen & bearbeiten</div>
                                </div>
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

        </div>
    </div>
</x-app-layout>
