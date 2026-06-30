<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Verwaltung</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 mb-8 text-stone-700">
                Administration des Heldenregisters. Sei behutsam – lieber fragen als Versagen ;-)
            </div>

            @php
                $card = fn(string $title, string $subtitle, string $img, string $href) =>
                    compact('title', 'subtitle', 'img', 'href');

                $groups = [
                    [
                        'title' => 'Helden & Spieler',
                        'icon'  => 'shield alternate',
                        'cards' => [
                            $card('Portal-Nutzer',    'Nutzer & Rollen',               'verwaltung_portal-nutzer.jpg',     route('admin.users.index')),
                            $card('Spieler',          'Alle Spieler/Teilnehmer',       'verwaltung_spieler.jpg',           route('admin.players.index')),
                            $card('Helden-Klassen',   'Klassen anlegen & pflegen',     'verwaltung_helden-klassen.jpg',    route('admin.hero-classes.index')),
                            $card('Gruppen',          'Gilden & Trupps verwalten',     'verwaltung_helden-gruppen.jpg',    route('admin.groups.index')),
                            $card('Fertigkeiten',     'Fertigkeiten-Katalog pflegen',  'verwaltung_fertigkeiten.jpg',      route('admin.skills.index')),
                            $card('Perlenfarben',     'Perlenfarben pflegen',          'verwaltung_perlenfarbe.jpg',       route('admin.perl-colors.index')),
                            $card('EP-Buchungsarten', 'EP-Buchungsarten pflegen',      'verwaltung_ep-buchungsarten.jpg',  route('admin.ep-transaction-types.index')),
                            $card('Heldenausweise',   'Ausweise generieren & zuweisen','verwaltung_heldenausweise.jpg',    route('admin.id-cards.index')),
                        ],
                    ],
                    [
                        'title' => 'Veranstaltungen',
                        'icon'  => 'calendar alternate outline',
                        'cards' => [
                            $card('Veranstaltungen',  'Abenteuer administrieren',      'verwaltung_veranstaltungen.jpg',   route('adventures.manage-index')),
                            $card('Orte',             'Veranstaltungsorte pflegen',    'verwaltung_orte.jpg',              route('admin.locations.index')),
                            $card('Kategorien',       'Event-Kategorien pflegen',      'verwaltung_event-kategorien.jpg',  route('admin.event-categories.index')),
                            $card('Auftraggeber',     'Auftraggeber pflegen',          'verwaltung_auftraggeber.jpg',      route('admin.event-clients.index')),
                            $card('Teilnahme-Rollen', 'Event-Rollen pflegen',          'verwaltung_teilnahme-rollen.jpg',  route('admin.event-roles.index')),
                            $card('Event-Status',     'Status-Lookups pflegen',        'verwaltung_event-status.jpg',      route('admin.event-statuses.index')),
                        ],
                    ],
                    [
                        'title' => 'System & Portal',
                        'icon'  => 'cog',
                        'cards' => [
                            $card('Rollen & Rechte',      'Berechtigungsübersicht',        'verwaltung_rollen-rechte.jpg',        route('admin.roles.index')),
                            $card('Portal-Einstellungen', 'Vereins-Settings bearbeiten',   'verwaltung_portal-einstellungen.jpg', route('admin.settings.index')),
                            $card('Audit-Log',            'Admin-Aktionen protokolliert',  'verwaltung_audit-log.jpg',            route('admin.audit-logs.index')),
                        ],
                    ],
                ];
            @endphp

            @foreach ($groups as $group)
                <div class="mb-10">
                    <h3 class="font-uncial text-xl text-waldritter mb-4 flex items-center gap-2">
                        <i class="{{ $group['icon'] }} icon" style="color:#5a3a22;"></i>
                        {{ $group['title'] }}
                    </h3>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($group['cards'] as $c)
                            <a href="{{ $c['href'] }}"
                               class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                                <div class="h-36 overflow-hidden">
                                    <img src="/images/{{ $c['img'] }}" alt="" aria-hidden="true" loading="lazy"
                                         width="400" height="144"
                                         class="w-full h-full object-cover group-hover:scale-105 transition">
                                </div>
                                <div class="p-3 text-center">
                                    <div class="font-uncial text-base text-waldritter">{{ $c['title'] }}</div>
                                    <div class="text-xs text-stone-600">{{ $c['subtitle'] }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach

        </div>
    </div>
</x-app-layout>
