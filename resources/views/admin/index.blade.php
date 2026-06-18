<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Verwaltung</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/60 border-2 border-[#5a3a22]/30 rounded-lg p-4 mb-6 text-stone-700">
                Administration des Heldenregisters. Sei behutsam – lieber fragen als Versagen ;-)
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $cards = [
                        ['Portal-Nutzer', 'Nutzer & Rollen', 'admin_users.jpg', route('admin.users.index')],
                        ['Spieler', 'Alle Spieler/Teilnehmer', 'admin_players.jpg', route('admin.players.index')],
                        ['Veranstaltungen', 'Abenteuer administrieren', 'admin_events.jpg', route('adventures.manage-index')],
                        ['Helden-Klassen', 'Klassen anlegen & pflegen', 'admin_classes.jpg', route('admin.hero-classes.index')],
                        ['Orte', 'Veranstaltungsorte pflegen', 'admin_orte.jpg', route('admin.locations.index')],
                        ['Kategorien', 'Event-Kategorien pflegen', 'admin_katagorien.jpg', route('admin.event-categories.index')],
                        ['Auftraggeber', 'Auftraggeber pflegen', 'admin_auftraggeber.jpg', route('admin.event-clients.index')],
                        ['Teilnahme-Rollen', 'Event-Rollen pflegen', 'admin_rollen.jpg', route('admin.event-roles.index')],
                        ['Event-Status', 'Status-Lookups pflegen', 'admin_katagorien.jpg', route('admin.event-statuses.index')],
                        ['Gruppen', 'Gilden & Trupps verwalten', 'admin_players.jpg', route('admin.groups.index')],
                        ['Audit-Log', 'Admin-Aktionen protokolliert', 'admin_users.jpg', route('admin.audit-logs.index')],
                        ['Portal-Einstellungen', 'Vereins-Settings bearbeiten', 'admin_katagorien.jpg', route('admin.settings.index')],
                        ['Rollen & Rechte', 'Berechtigungsübersicht', 'admin_players.jpg', route('admin.roles.index')],
                        ['Fertigkeiten', 'Fertigkeiten-Katalog pflegen', 'admin_classes.jpg', route('admin.skills.index')],
                        ['Perlenfarben', 'Perlenfarben pflegen', 'admin_classes.jpg', route('admin.perl-colors.index')],
                        ['EP-Buchungsarten', 'EP-Buchungsarten pflegen', 'admin_katagorien.jpg', route('admin.ep-transaction-types.index')],
                    ];
                @endphp
                @foreach ($cards as [$title, $subtitle, $img, $href])
                    <a href="{{ $href }}"
                       class="group block rounded-lg overflow-hidden border-2 border-[#5a3a22]/40 bg-white/60 shadow hover:shadow-xl hover:-translate-y-1 transition">
                        <div class="h-44 overflow-hidden">
                            <img src="/images/{{ $img }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition">
                        </div>
                        <div class="p-4 text-center">
                            <div class="font-uncial text-lg text-waldritter">{{ $title }}</div>
                            <div class="text-sm text-stone-600">{{ $subtitle }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
