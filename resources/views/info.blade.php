<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">
                Funktionsübersicht &amp; Hilfe
            </h2>
            <span class="text-sm text-stone-500">{{ config('portal.organization_short') }} {{ config('portal.name') }}</span>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

            {{-- Einleitung --}}
            <div class="bg-[#fdf6e3] border-2 border-[#5a3a22]/30 rounded-xl p-6 sm:p-8">
                <h3 class="font-uncial text-xl text-waldritter mb-3">Was ist das {{ config('portal.organization_short') }} {{ config('portal.name') }}?</h3>
                <p class="text-stone-700 leading-relaxed mb-4">
                    Das {{ config('portal.name') }} ist das zentrale Verwaltungsportal für {{ config('portal.larp_type') }} der {{ config('portal.organization_short') }}.
                    Es bündelt die gesamte Organisation rund um Charaktere, Veranstaltungen und Teilnehmende
                    in einer modernen, sicheren Webanwendung – und löst damit das bisherige
                    PHP-Altsystem vollständig ab.
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-5">
                    <div class="bg-white/70 rounded-lg p-4 border border-[#5a3a22]/20 text-center">
                        <div class="text-3xl font-bold text-waldritter">{{ \App\Models\Hero::count() }}</div>
                        <div class="text-sm text-stone-600 mt-1">Helden im Register</div>
                    </div>
                    <div class="bg-white/70 rounded-lg p-4 border border-[#5a3a22]/20 text-center">
                        <div class="text-3xl font-bold text-waldritter">{{ \App\Models\Adventure::count() }}</div>
                        <div class="text-sm text-stone-600 mt-1">Abenteuer angelegt</div>
                    </div>
                    <div class="bg-white/70 rounded-lg p-4 border border-[#5a3a22]/20 text-center">
                        <div class="text-3xl font-bold text-waldritter">{{ \App\Models\Player::count() }}</div>
                        <div class="text-sm text-stone-600 mt-1">Spieler registriert</div>
                    </div>
                </div>
            </div>

            {{-- ===== ABSCHNITT: FÜR ELTERN & SPIELER ===== --}}
            <section>
                <h3 class="font-uncial text-lg text-waldritter mb-4 flex items-center gap-2">
                    <i class="users icon text-[#5a3a22]"></i> Für Eltern &amp; Betreuer
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="user plus icon text-waldritter"></i> Konto &amp; Registrierung
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Selbstregistrierung mit E-Mail-Verifizierung</li>
                            <li>Passwort-Reset per E-Mail (gebrandet, Deutsch)</li>
                            <li>Kontoaktivierung &amp; -deaktivierung durch Admin</li>
                            <li>Profil mit vollständiger Adresse der erziehungsberechtigten Person</li>
                            <li>Kontolöschung (Soft-Delete, wiederherstellbar)</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="child icon text-waldritter"></i> Spielerverwaltung
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Eigene Kinder/Spieler anlegen und verwalten</li>
                            <li>Profilfoto (Avatar) hochladen und zuschneiden</li>
                            <li>Geburtsdatum und Adresse des Kindes pflegen</li>
                            <li>Mehrere Betreuer je Spieler möglich (z. B. beide Elternteile)</li>
                            <li>Aktiven Helden je Spieler festlegen</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="calendar alternate outline icon text-waldritter"></i> Anmeldungen zu Abenteuern
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Abenteuer in der Listenansicht oder Kalender durchsuchen</li>
                            <li>Anmeldung mit Auswahl von Spieler, Held und Teilnahmerolle</li>
                            <li>Automatische Warteliste bei vollem Veranstaltungsort</li>
                            <li>Automatisches Nachrücken bei Stornierung eines Platzes</li>
                            <li>Eigene Anmeldung stornieren</li>
                            <li>Kosten &amp; Teilnahmebeitrag vor der Anmeldung sichtbar</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="bell outline icon text-waldritter"></i> E-Mail-Benachrichtigungen
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Buchungseingang, Bestätigung, Ablehnung</li>
                            <li>Stornierungsbestätigung</li>
                            <li>Zahlungseingang</li>
                            <li>Nachrücker von der Warteliste</li>
                            <li>Erinnerung wenige Tage vor dem Abenteuer</li>
                            <li>Absage einer Veranstaltung</li>
                            <li>Jede Benachrichtigung einzeln im Profil deaktivierbar</li>
                        </ul>
                    </div>

                </div>
            </section>

            {{-- ===== ABSCHNITT: HELDEN & CHARAKTERE ===== --}}
            <section>
                <h3 class="font-uncial text-lg text-waldritter mb-4 flex items-center gap-2">
                    <i class="id card outline icon text-[#5a3a22]"></i> Helden &amp; Charaktere
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="pencil alternate icon text-waldritter"></i> Charakterverwaltung
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Charaktername, Klasse, Herkunft, Beschreibung (Steckbrief)</li>
                            <li>Charakterfoto hochladen und zuschneiden (1:1)</li>
                            <li>Galerie mit bis zu 4 Fotos (freies Seitenverhältnis)</li>
                            <li>Status: Erstblickung, Verschollen, Inaktiv</li>
                            <li>Mehrere Klassen je Held möglich</li>
                            <li>Abenteuerhistorie (alle besuchten Events je Held)</li>
                            <li>Charakterbogen als PDF herunterladen</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="star outline icon text-waldritter"></i> Erfahrungspunkte (EP)
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>EP-Konto je Held mit Transaktionsverlauf</li>
                            <li>Automatische EP-Vergabe nach Teilnahme an Events</li>
                            <li>Manuelle EP-Buchungen durch Bürokrat (Korrektur, Sondervergabe)</li>
                            <li>Perlen-System: Farben symbolisieren verschiedene EP-Arten</li>
                            <li>EP-Kontoauszug als CSV exportierbar</li>
                            <li>Fertigkeiten kosten beim Erwerb EP</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="sitemap icon text-waldritter"></i> Fertigkeiten &amp; Fertigkeitsbaum
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Fertigkeiten nach Klasse organisiert</li>
                            <li>Voraussetzungen (Skill-Tree) werden geprüft</li>
                            <li>Visuelle Baumdarstellung je Klasse</li>
                            <li>Icon je Fertigkeit (100×100 px Bild)</li>
                            <li>Fertigkeitenkatalog für Teamer, Lehrmeister und Spielleiter</li>
                            <li>Anzahl aktiver Helden je Fertigkeit einsehbar</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="globe icon text-waldritter"></i> Öffentliches Heldenprofil &amp; Siegel
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Jeder Held erhält ein eindeutiges 6-stelliges Helden-Siegel</li>
                            <li>Physischer Heldenausweis (PDF, 7,5 × 10 cm, druckfertig)</li>
                            <li>Öffentliche Seite <code class="text-xs bg-stone-100 px-1 rounded">/h/{siegel}</code> ohne Realnamen</li>
                            <li>Suchfunktion per Siegel, opt-in für Suchbarkeit</li>
                            <li>QR-Code im Heldenausweis und im Portal</li>
                            <li>Sichtbarkeit und Suchbarkeit je Held einzeln steuerbar</li>
                            <li>Rate-Limiting: max. 30 Anfragen/min je IP gegen Enumeration</li>
                        </ul>
                    </div>

                </div>
            </section>

            {{-- ===== ABSCHNITT: ABENTEUER & EVENTS ===== --}}
            <section>
                <h3 class="font-uncial text-lg text-waldritter mb-4 flex items-center gap-2">
                    <i class="map signs icon text-[#5a3a22]"></i> Abenteuer &amp; Veranstaltungen
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="edit outline icon text-waldritter"></i> Veranstaltungsverwaltung
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Anlegen und Bearbeiten von Events mit allen Stammdaten</li>
                            <li>Status-Workflow: Planung → Offen → Durchgeführt → Abgesagt</li>
                            <li>Veranstaltungsort, Kategorie, Auftraggeber, Spielleiter zuweisen</li>
                            <li>Maximale Teilnehmerzahl und Teilnahmerollen konfigurieren</li>
                            <li>Event absagen mit automatischer Mail an alle Angemeldeten</li>
                            <li>Listen- und Kalenderansicht</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="clipboard list icon text-waldritter"></i> Buchungsverwaltung
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Anmeldungen einsehen, bestätigen, ablehnen</li>
                            <li>Bezahlt-Status pro Anmeldung verfolgen</li>
                            <li>Gast-Anmeldung ohne Konto möglich</li>
                            <li>Anmeldedaten nachträglich bearbeiten</li>
                            <li>Warteliste mit automatischem Nachrücken</li>
                            <li>Teilnehmerliste als PDF exportieren</li>
                            <li>Belegungs- und Teilnahmereport als CSV</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="check circle outline icon text-waldritter"></i> Check-in &amp; Anwesenheit
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Digitaler Check-in je Teilnehmer vor Ort</li>
                            <li>Digitale Unterschrift erfassen</li>
                            <li>Abmeldefunktion direkt beim Event</li>
                            <li>Automatische EP-Vergabe für alle anwesenden Teilnehmer</li>
                            <li>Check-in-Übersicht im Verwaltungs-Tab</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="users icon text-waldritter"></i> Teamer &amp; Lehrmeister
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Teamer und Lehrmeister melden sich gesondert zu Events an</li>
                            <li>Projektleitung lädt Teamer per E-Mail ein</li>
                            <li>Teamer-Rolle je Anmeldung festlegbar</li>
                            <li>Teamer-Übersicht im Verwaltungs-Modal (inkl. NSC-Ansicht)</li>
                            <li>Teamer-Einladungen im Profil deaktivierbar</li>
                        </ul>
                    </div>

                </div>
            </section>

            {{-- ===== ABSCHNITT: ADMINISTRATION ===== --}}
            <section>
                <h3 class="font-uncial text-lg text-waldritter mb-4 flex items-center gap-2">
                    <i class="cogs icon text-[#5a3a22]"></i> Verwaltung &amp; Administration
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="user secret icon text-waldritter"></i> Nutzer- &amp; Rollenverwaltung
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Alle Nutzerkonten einsehen, anlegen, bearbeiten</li>
                            <li>Rollen zuweisen: Admin, Bürokrat, Projektleitung, Spielleiter, Lehrmeister, Teamer, Teilnehmer</li>
                            <li>Konto sperren (deaktivieren) und reaktivieren</li>
                            <li>Konto dauerhaft löschen (Soft-Delete + Wiederherstellung)</li>
                            <li>Rollenänderungen werden im Audit-Log protokolliert</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="database icon text-waldritter"></i> Stammdatenpflege
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Veranstaltungsorte (mit Adresse und Kapazität)</li>
                            <li>Event-Kategorien und Auftraggeber</li>
                            <li>Teilnahmerollen (z. B. Kämpfer, Händler, NSC)</li>
                            <li>Event-Status-Definitionen</li>
                            <li>Helden-Klassen und Fertigkeiten mit Voraussetzungen</li>
                            <li>EP-Buchungsarten und Perlenfarben</li>
                            <li>Portal-Einstellungen (Key-Value-Konfiguration)</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="shield alternate icon text-waldritter"></i> Audit-Log
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Protokollierung aller relevanten Änderungen im Portal</li>
                            <li>Wer hat was wann geändert (mit vorherigem und neuem Wert)</li>
                            <li>Rollenänderungen immer protokolliert</li>
                            <li>Filterbar nach Benutzer, Aktion und Zeitraum</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="file alternate outline icon text-waldritter"></i> Exporte &amp; Berichte
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Charakterbogen je Held als PDF</li>
                            <li>EP-Kontoauszug je Held als CSV</li>
                            <li>Teilnehmerliste je Event als PDF</li>
                            <li>Belegungsreport je Event als CSV</li>
                            <li>Spieler-/Mitgliederübersicht als CSV (DSGVO-konform)</li>
                            <li>Heldenausweis-Generator (druckfertige PDF, Duplexdruck)</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5 md:col-span-2">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="object group outline icon text-waldritter"></i> Gruppen
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside sm:columns-2">
                            <li>Spielergruppen anlegen und verwalten</li>
                            <li>Helden als Mitglieder einer Gruppe zuordnen</li>
                            <li>Gruppenansicht mit Mitgliederliste und Helden</li>
                            <li>Gruppenanzeige im öffentlichen Heldenprofil</li>
                        </ul>
                    </div>

                </div>
            </section>

            {{-- ===== ABSCHNITT: SICHERHEIT & DATENSCHUTZ ===== --}}
            <section>
                <h3 class="font-uncial text-lg text-waldritter mb-4 flex items-center gap-2">
                    <i class="lock icon text-[#5a3a22]"></i> Sicherheit &amp; Datenschutz
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="key icon text-waldritter"></i> Authentifizierung &amp; Zugriffsschutz
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Passwörter werden ausschließlich gehasht gespeichert (bcrypt)</li>
                            <li>E-Mail-Verifizierung vor dem ersten Zugang</li>
                            <li>CSRF-Schutz auf allen Formularen</li>
                            <li>Gesperrte Konten werden sofort und auch bei aktiver Sitzung abgewiesen</li>
                            <li>Rate-Limiting auf Login, Suche und öffentlichen Endpunkten</li>
                            <li>Alle Routen hinter <code class="text-xs bg-stone-100 px-1 rounded">auth + verified</code>-Middleware</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="sitemap icon text-waldritter"></i> Rollen &amp; Rechte
                        </div>
                        <p class="text-sm text-stone-600 mb-2">Jede Aktion ist durch ein feingranulares Berechtigungssystem abgesichert:</p>
                        <div class="overflow-x-auto">
                            <table class="text-xs w-full">
                                <thead>
                                    <tr class="text-left text-stone-500 border-b border-stone-200">
                                        <th class="pb-1 pr-3">Rolle</th>
                                        <th class="pb-1">Kernrechte</th>
                                    </tr>
                                </thead>
                                <tbody class="text-stone-600 divide-y divide-stone-100">
                                    <tr class="py-1">
                                        <td class="pr-3 py-1 font-medium whitespace-nowrap">Teilnehmer</td>
                                        <td class="py-1">Eigene Spieler, Events buchen</td>
                                    </tr>
                                    <tr>
                                        <td class="pr-3 py-1 font-medium whitespace-nowrap">Teamer</td>
                                        <td class="py-1">Teamer-Anmeldung, Event ansehen</td>
                                    </tr>
                                    <tr>
                                        <td class="pr-3 py-1 font-medium whitespace-nowrap">Lehrmeister</td>
                                        <td class="py-1">Wie Teamer + Helden ansehen, Fertigkeiten freischalten, Anwesenheit</td>
                                    </tr>
                                    <tr>
                                        <td class="pr-3 py-1 font-medium whitespace-nowrap">Spielleiter</td>
                                        <td class="py-1">Events ansehen, Check-in verwalten</td>
                                    </tr>
                                    <tr>
                                        <td class="pr-3 py-1 font-medium whitespace-nowrap">Projektleitung</td>
                                        <td class="py-1">Events anlegen &amp; verwalten, Buchungen bearbeiten</td>
                                    </tr>
                                    <tr>
                                        <td class="pr-3 py-1 font-medium whitespace-nowrap">Bürokrat</td>
                                        <td class="py-1">Heldenregister bearbeiten, EP buchen, Ausweise</td>
                                    </tr>
                                    <tr>
                                        <td class="pr-3 py-1 font-medium whitespace-nowrap">Admin</td>
                                        <td class="py-1">Vollzugriff inkl. Nutzerverwaltung, Stammdaten, Audit-Log</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="privacy icon text-waldritter"></i> Datenschutz (DSGVO)
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Öffentliche Heldenseiten enthalten <strong>keine Realnamen</strong></li>
                            <li>Sichtbarkeit und Suchbarkeit je Held einzeln opt-in/opt-out</li>
                            <li>Soft-Delete: Daten werden nicht sofort vernichtet, sondern deaktiviert</li>
                            <li>DSGVO-Art.-17-Anonymisierungsfunktion für Spieler</li>
                            <li>Spielerliste-Export enthält nur die notwendigen Felder</li>
                            <li>Datenschutzhinweis bei Erfassung von Minderjährigendaten</li>
                            <li>Nutzer sehen im Profil alle über sie gespeicherten Datenkategorien</li>
                            <li>Kontaktmöglichkeit für Löschanfragen im Profil verlinkt</li>
                        </ul>
                    </div>

                    <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-2 font-semibold text-stone-800">
                            <i class="server icon text-waldritter"></i> Technische Sicherheit
                        </div>
                        <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                            <li>Laravel 12 – industrieerprobtes PHP-Framework mit aktivem Sicherheitsteam</li>
                            <li>SQL-Injection-Schutz durch Eloquent ORM (Prepared Statements)</li>
                            <li>XSS-Schutz durch Blade-Template-Escaping</li>
                            <li>Datei-Uploads werden auf Typ und Größe geprüft; keine ausführbaren Dateien</li>
                            <li>Bilder werden serverseitig neu kodiert (kein direktes Durchreichen)</li>
                            <li>Automatische Datenbankbackups konfiguriert</li>
                            <li>Fehler-Monitoring und strukturiertes Logging</li>
                            <li>CI-Pipeline: Tests und Code-Style werden bei jedem Push geprüft</li>
                        </ul>
                    </div>

                </div>
            </section>

            {{-- ===== ABSCHNITT: BENACHRICHTIGUNGEN ===== --}}
            <section>
                <h3 class="font-uncial text-lg text-waldritter mb-4 flex items-center gap-2">
                    <i class="bell icon text-[#5a3a22]"></i> Benachrichtigungssystem
                </h3>
                <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <div class="font-semibold text-stone-700 mb-2">Portal-Benachrichtigungen (Glocke)</div>
                            <p class="text-sm text-stone-600 mb-2">Erscheinen im Portal in Echtzeit und können nicht deaktiviert werden:</p>
                            <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                                <li>Teamer-Einladung zu einem Event</li>
                                <li>Stornierung einer Teilnehmer-Buchung (→ Projektleitung)</li>
                                <li>Neue Nutzerregistrierung (→ Admin)</li>
                            </ul>
                        </div>
                        <div>
                            <div class="font-semibold text-stone-700 mb-2">E-Mail-Benachrichtigungen</div>
                            <p class="text-sm text-stone-600 mb-2">Werden asynchron per Queue versendet, jede einzeln abschaltbar:</p>
                            <ul class="text-sm text-stone-600 space-y-1 list-disc list-inside">
                                <li>Buchungseingang, -bestätigung, -ablehnung</li>
                                <li>Stornierungsbestätigung (Teilnehmer)</li>
                                <li>Zahlungseingang, Warteliste/Nachrücker</li>
                                <li>Event-Absage, Erinnerung vor Event</li>
                                <li>Teamer-Einladung</li>
                                <li>Stornierungsmeldung (Projektleitung)</li>
                                <li>Neue Registrierung (Admin)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===== ABSCHNITT: TECHNIK ===== --}}
            <section>
                <h3 class="font-uncial text-lg text-waldritter mb-4 flex items-center gap-2">
                    <i class="code icon text-[#5a3a22]"></i> Technische Grundlage
                </h3>
                <div class="bg-white/70 border border-[#5a3a22]/20 rounded-xl p-5">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center text-sm">
                        <div class="p-3 bg-stone-50 rounded-lg border border-stone-200">
                            <div class="font-bold text-stone-700 text-base">Laravel 12</div>
                            <div class="text-stone-500 text-xs mt-0.5">PHP Framework</div>
                        </div>
                        <div class="p-3 bg-stone-50 rounded-lg border border-stone-200">
                            <div class="font-bold text-stone-700 text-base">PHP 8.3</div>
                            <div class="text-stone-500 text-xs mt-0.5">Serversprache</div>
                        </div>
                        <div class="p-3 bg-stone-50 rounded-lg border border-stone-200">
                            <div class="font-bold text-stone-700 text-base">MySQL</div>
                            <div class="text-stone-500 text-xs mt-0.5">Datenbank</div>
                        </div>
                        <div class="p-3 bg-stone-50 rounded-lg border border-stone-200">
                            <div class="font-bold text-stone-700 text-base">Fomantic UI</div>
                            <div class="text-stone-500 text-xs mt-0.5">Oberfläche</div>
                        </div>
                        <div class="p-3 bg-stone-50 rounded-lg border border-stone-200">
                            <div class="font-bold text-stone-700 text-base">Queue</div>
                            <div class="text-stone-500 text-xs mt-0.5">Async E-Mails</div>
                        </div>
                        <div class="p-3 bg-stone-50 rounded-lg border border-stone-200">
                            <div class="font-bold text-stone-700 text-base">GitHub CI</div>
                            <div class="text-stone-500 text-xs mt-0.5">Tests &amp; Style</div>
                        </div>
                        <div class="p-3 bg-stone-50 rounded-lg border border-stone-200">
                            <div class="font-bold text-stone-700 text-base">PWA-fähig</div>
                            <div class="text-stone-500 text-xs mt-0.5">Installierbar</div>
                        </div>
                        <div class="p-3 bg-stone-50 rounded-lg border border-stone-200">
                            <div class="font-bold text-stone-700 text-base">Mobiloptimiert</div>
                            <div class="text-stone-500 text-xs mt-0.5">Responsive</div>
                        </div>
                    </div>
                    <p class="text-xs text-stone-500 mt-4 text-center">
                        Das Portal ist als Progressive Web App installierbar und vollständig mobiloptimiert.
                        Alle Abhängigkeiten werden lokal gebündelt – kein CDN-Aufruf im Betrieb.
                    </p>
                </div>
            </section>

            {{-- Fußzeile --}}
            <div class="text-center text-xs text-stone-400 pb-4">
                {{ config('portal.organization_short') }} {{ config('portal.name') }} &mdash; Interne Funktionsübersicht &mdash; {{ now()->format('Y') }}
            </div>

        </div>
    </div>
</x-app-layout>
