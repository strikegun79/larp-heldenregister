<x-app-layout>
    <x-slot name="header">
        <h2 class="font-uncial text-2xl text-waldritter leading-tight">Rollen & Berechtigungen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Berechtigungsmatrix --}}
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg overflow-hidden">
                <x-mobile.cards-or-table>
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-black/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Rolle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 uppercase">Berechtigungen</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 uppercase">Nutzer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 text-stone-800">
                        @foreach ($roles as $role)
                            <tr class="align-top">
                                <td class="px-6 py-4 font-medium" data-label="Rolle">{{ $role->label }}</td>
                                <td class="px-6 py-4 font-mono text-sm text-stone-500" data-label="Slug">{{ $role->slug }}</td>
                                <td class="px-6 py-4 text-sm" data-label="Berechtigungen">
                                    @if ($role->permissions)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($role->permissions as $perm)
                                                <span class="inline-block bg-stone-100 text-stone-700 rounded px-2 py-0.5 text-xs font-mono">{{ $perm }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-stone-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right tabular-nums" data-label="Nutzer">{{ $role->users_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </x-mobile.cards-or-table>
            </div>

            {{-- DSGVO-Rollenübersicht --}}
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow sm:rounded-lg p-6">

                <h2 class="ui header">
                    <i class="shield alternate icon"></i>
                    <div class="content">
                        Datenschutz &amp; DSGVO — Rollenübersicht
                        <div class="sub header">Welche Rolle kann welche personenbezogenen Daten einsehen — Grundlage für Art. 13 Abs. 1 lit. e DSGVO</div>
                    </div>
                </h2>

                {{-- Hinweis Gesundheitsdaten --}}
                <div class="ui message">
                    <div class="header">
                        <i class="shield alternate icon"></i>
                        Besonders schützenswerte Daten nach Art. 9 DSGVO
                    </div>
                    <p>
                        Das Heldenregister verarbeitet <strong>Gesundheitsdaten Minderjähriger</strong>: Allergien und Medikation
                        werden im Spielerprofil, in Veranstaltungsanmeldungen und in Teamer-Anmeldungen erfasst. Diese Daten fallen
                        unter Art. 9 Abs. 1 DSGVO (besondere Kategorien personenbezogener Daten) und unterliegen einem erhöhten
                        Schutzbedarf.
                    </p>
                    <p>
                        Der Einwilligungszeitstempel (<code>health_data_consent_at</code>) ist in allen drei Tabellen implementiert:
                        <code>bookings</code>, <code>players</code> und <code>teamer_signups</code>. Für Minderjährige dokumentiert
                        <code>parental_consent_at</code> in <code>players</code> die elterliche Einwilligung nach Art. 8 DSGVO.
                    </p>
                    <p class="mb-0">
                        Handschriftliche Unterschriften (biometrische Daten) sind <strong>AES-256-verschlüsselt</strong>
                        in der Datenbank gespeichert (<code>bookings.signature</code>, <code>encrypted</code>-Cast) und werden
                        automatisch 30 Tage nach Veranstaltungsende gelöscht.
                    </p>
                </div>

                {{-- Tabelle: Rollen und Datenkategorien --}}
                <h3 class="ui dividing header">Datenkategorien je Rolle</h3>

                <div class="ui small warning message">
                    <p class="mb-0">
                        <strong>Legende:</strong>
                        <span class="ui green tiny label">Lesen</span> Lesezugriff &ensp;
                        <span class="ui blue tiny label">L+S</span> Lesen und Schreiben &ensp;
                        <span class="ui red tiny label">⚠ L</span> / <span class="ui red tiny label">⚠ L+S</span> Zugriff auf besonders schützenswerte Daten &ensp;
                        <span class="ui grey tiny label">Nein</span> kein Zugriff
                    </p>
                </div>

                <div style="overflow-x: auto;">
                    <table class="ui celled structured small table" style="min-width: 1100px;">
                        <thead>
                            <tr>
                                <th rowspan="2">Rolle</th>
                                <th colspan="2" class="center aligned">Nutzerkonto<br><small>(Erziehungsberechtigte)</small></th>
                                <th colspan="3" class="center aligned">Spielerprofil<br><small>(Kind / Jugendliche/r)</small></th>
                                <th colspan="2" class="center aligned">Heldendaten</th>
                                <th colspan="3" class="center aligned">Veranstaltungen</th>
                                <th class="center aligned">Gruppen</th>
                                <th class="center aligned">Komm.</th>
                                <th colspan="2" class="center aligned">Umfragen</th>
                                <th colspan="2" class="center aligned">Administration</th>
                            </tr>
                            <tr>
                                <th title="Name, E-Mail, Telefon, Adresse der Erziehungsberechtigten">Kontakt&shy;daten</th>
                                <th title="Anmeldedatum, Login-Zeitstempel, Benachrichtigungseinstellungen">Meta&shy;daten</th>
                                <th title="Vorname, Nachname, Geburtsdatum, Geschlecht, Adresse, Profilfoto">Stamm&shy;daten</th>
                                <th title="Allergien, Medikation — Art. 9 DSGVO" class="negative">Gesundheits&shy;daten ⚠</th>
                                <th title="Anmeldungen zu Veranstaltungen, Status, Bezahlstatus">Buchungs&shy;verlauf</th>
                                <th title="Charaktername, Klassen, Skills, EP-Transaktionen, Charakterfoto">Charakter&shy;daten</th>
                                <th title="Anlegen, Bearbeiten, Löschen von Helden und EP-Buchungen">Charakter&shy;verwaltung</th>
                                <th title="Termine, Ort, Kapazität, Preise">Event&shy;informationen</th>
                                <th title="Alle Anmeldungen eines Events inkl. Gesundheitsdaten, Notfallkontakt" class="negative">Teilnehmer&shy;listen ⚠</th>
                                <th title="PDF-Export der Teilnehmerliste mit Adress- und Gesundheitsdaten" class="negative">Teilnehmer&shy;PDF ⚠</th>
                                <th title="Welcher Held welcher Gruppe angehört">Gruppen&shy;mitglied&shy;schaften</th>
                                <th title="Newsletter-Abonnenten, Versand-Kampagnen, Einwilligungsnachweise (newsletter.manage)">Newsletter&shy;versand</th>
                                <th title="Name, E-Mail der Eingeladenen, Altersgruppen-Typ, IP-Adresse der Antwort">Umfrage&shy;einladungen</th>
                                <th title="Einzelantworten inkl. Freitext, IP-Adresse, Zeitstempel">Umfrage&shy;ergebnisse</th>
                                <th title="Vollständige Nutzerliste, Rollenzuweisung, Aktivierung">Nutzer&shy;verwaltung</th>
                                <th title="Alle Spieler, CSV-Export, Betreuer-Zuordnungen">Spieler&shy;administration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="negative">
                                <td><strong>Administrator</strong><br><small class="ui grey text">admin</small></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L+S</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                            </tr>
                            <tr class="warning">
                                <td><strong>Bürokrat</strong><br><small class="ui grey text">registrar</small></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                            </tr>
                            <tr class="warning">
                                <td><strong>Projektleitung</strong><br><small class="ui grey text">project_lead</small></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui blue tiny label">L+S</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                            </tr>
                            <tr>
                                <td><strong>Spielleiter</strong><br><small class="ui grey text">game_master</small></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                            </tr>
                            <tr>
                                <td><strong>Lehrmeister</strong><br><small class="ui grey text">lehrmeister</small></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                            </tr>
                            <tr>
                                <td><strong>Teamer</strong><br><small class="ui grey text">teamer</small></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                            </tr>
                            <tr>
                                <td><strong>Event buchen</strong><br><small class="ui grey text">event_booking</small></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui red tiny label">⚠ L</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                            </tr>
                            <tr>
                                <td><strong>Teilnehmer</strong><br><small class="ui grey text">participant</small></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui green tiny label">Lesen</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                                <td class="center aligned"><span class="ui grey tiny label">Nein</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="ui small info message mt-4">
                    <i class="info circle icon"></i>
                    <strong>Hinweis zu Gesundheitsdaten bei Rollen unterhalb Spielleiter:</strong>
                    Die Berechtigung <code>player.view</code> erlaubt formal den Zugriff auf das Spielerprofil
                    einschließlich Allergien und Medikation. In der Praxis sehen diese Rollen jedoch
                    <strong>ausschließlich eigene oder betreute Spieler</strong> — die <code>PlayerPolicy</code>
                    verhindert den Zugriff auf Fremdprofile technisch.
                </div>

                {{-- Datenkategorien-Erklärung --}}
                <h3 class="ui dividing header">Datenkategorien im Detail</h3>

                <div class="ui styled fluid accordion">

                    <div class="title">
                        <i class="dropdown icon"></i>
                        Kontaktdaten des Nutzerkontos (Erziehungsberechtigte)
                    </div>
                    <div class="content">
                        <p><strong>Felder:</strong> Vorname, Nachname, E-Mail-Adresse, Postanschrift (Straße, Hausnummer, PLZ, Ort)</p>
                        <p><strong>Tabelle:</strong> <code>users</code></p>
                        <p><strong>Zweck:</strong> Kommunikation mit Erziehungsberechtigten, Versand von Veranstaltungsbestätigungen und -erinnerungen, Notfallkontakt.</p>
                        <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung) für Veranstaltungsteilnahme; Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse) für Sicherheitsbenachrichtigungen.</p>
                        <p class="mb-0"><strong>Zugriff:</strong> Alle Rollen mit <code>profile.view</code> können eigene Kontaktdaten einsehen. Die vollständige Nutzerliste mit allen Konten ist ausschließlich Administratoren (<code>users.manage</code>) zugänglich.</p>
                    </div>

                    <div class="title">
                        <i class="dropdown icon"></i>
                        Stammdaten des Spielers / Kindes
                    </div>
                    <div class="content">
                        <p><strong>Felder:</strong> Vorname, Nachname, Geburtsdatum, Geschlecht, Profilfoto, Postanschrift des Kindes (sofern abweichend)</p>
                        <p><strong>Tabelle:</strong> <code>players</code></p>
                        <p><strong>Zweck:</strong> Verwaltung der Teilnehmerdaten, Altersverifizierung für altersgruppen-spezifische Veranstaltungen.</p>
                        <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung); bei Minderjährigen unter 16 Jahren ergänzend Art. 8 DSGVO — Einwilligung der Erziehungsberechtigten erforderlich. Das Geburtsdatum ist zur Altersgruppenzuordnung zwingend erforderlich (Datenminimierung gewahrt).</p>
                        <p class="mb-0"><strong>Besonderheit Minderjährige:</strong> Spieler unter 16 Jahren können das System nicht eigenständig nutzen. Registrierung und Datenpflege erfolgen ausschließlich durch Erziehungsberechtigte (Nutzerkonto = Elternteil).</p>
                    </div>

                    <div class="title active">
                        <i class="dropdown icon"></i>
                        <strong class="ui red text">Gesundheitsdaten (Art. 9 DSGVO) — erhöhter Schutzbedarf</strong>
                    </div>
                    <div class="content active">
                        <p><strong>Felder:</strong> Allergien / Lebensmittelunverträglichkeiten (<code>allergien</code>), Medikation (<code>medikamente</code>), Einwilligungszeitstempel (<code>health_data_consent_at</code>)</p>
                        <p><strong>Tabellen:</strong> <code>players</code> (Profil-Hinterlegung), <code>bookings</code> (veranstaltungsbezogene Angabe), <code>teamer_signups</code> (Teamer-Anmeldung)</p>
                        <p><strong>Zweck:</strong> Sicherstellung der Gesundheitsversorgung und Fürsorge bei Veranstaltungen; Verpflegungsplanung (Allergien).</p>
                        <p><strong>Rechtsgrundlage:</strong> Art. 9 Abs. 2 lit. c DSGVO (Schutz lebenswichtiger Interessen) i.V.m. Art. 6 Abs. 1 lit. b DSGVO. <code>health_data_consent_at</code> dokumentiert die ausdrückliche Einwilligung (Art. 9 Abs. 2 lit. a DSGVO) in allen drei Tabellen (<code>bookings</code>, <code>players</code>, <code>teamer_signups</code>).</p>
                        <p><strong>Zugriff:</strong> Rollen mit <code>adventure.modify</code> können Gesundheitsdaten im Buchungskontext einsehen. Teilnehmer-PDF (Bürokrat, Projektleitung, Administrator) enthält ebenfalls Gesundheitsdaten. Der CSV-Spielerexport (nur Administrator) enthält Gesundheitsdaten aus dem Spielerprofil.</p>
                        <p class="mb-0"><strong>Aufbewahrung:</strong> Gesundheitsdaten in <code>bookings</code> und <code>teamer_signups</code> werden 2 Jahre nach Veranstaltungsende automatisch genullt (DsgvoPrune).</p>
                    </div>

                    <div class="title">
                        <i class="dropdown icon"></i>
                        Heldendaten / Charakterinformationen
                    </div>
                    <div class="content">
                        <p><strong>Felder:</strong> Charaktername, Charakterklassen, erlernte Fertigkeiten, EP-Buchungsverlauf, Charakterfoto, Hintergrundgeschichte, Status (aktiv / inaktiv / verschollen)</p>
                        <p><strong>Tabellen:</strong> <code>heroes</code>, <code>hero_hero_class</code> (Pivot), <code>hero_skill</code> (Pivot), <code>ep_transactions</code></p>
                        <p><strong>Zweck:</strong> Führung des LARP-Heldenregisters; Dokumentation der Charakterentwicklung über Veranstaltungen hinweg.</p>
                        <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung — Bestandteil des Heldenregister-Dienstes).</p>
                        <p><strong>Hinweis öffentliche Profile:</strong> Helden können unter <code>/h/{code}</code> öffentlich zugänglich gemacht werden (<code>public_visible</code>). Dies erfordert eine ausdrückliche Aktivierung durch Erziehungsberechtigte. Öffentliche Profile enthalten keinen Realnamen des Kindes.</p>
                        <p class="mb-0"><strong>EP-Buchungen als Bewegungsprofil:</strong> Der vollständige EP-Buchungsverlauf ermöglicht die Rekonstruktion eines detaillierten Anwesenheitsprofils des Kindes. Zugriff haben alle Rollen mit <code>heldenregister.view</code>.</p>
                    </div>

                    <div class="title">
                        <i class="dropdown icon"></i>
                        Buchungs- und Veranstaltungsdaten
                    </div>
                    <div class="content">
                        <p><strong>Felder:</strong> Angemeldete Person, Veranstaltung, Rolle, Anmeldestatus, Bezahlstatus, Wartelistenstatus, Fotoerlaubnis, Leihausstattung, Ernährungsbesonderheiten, Ermäßigung, Notfall-Erreichbarkeit, Kontakttelefon, digitale Unterschrift</p>
                        <p><strong>Tabelle:</strong> <code>bookings</code></p>
                        <p><strong>Zweck:</strong> Durchführung und Verwaltung von LARP-Veranstaltungen; Kapazitätsplanung; Abwicklung der Teilnahmebeiträge.</p>
                        <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung).</p>
                        <p class="mb-0"><strong>Unterschrift:</strong> Die digitale Unterschrift (<code>signature</code>) ist als biometrisches Merkmal (Art. 9 DSGVO) <strong>AES-256-verschlüsselt</strong> in der Datenbank gespeichert (<code>encrypted</code>-Cast, seit Sep. 2026). Sie wird 30 Tage nach Veranstaltungsende automatisch gelöscht (DsgvoPrune). Zugriff haben Projektleitung und Bürokrat.</p>
                    </div>

                    <div class="title">
                        <i class="dropdown icon"></i>
                        Umfragedaten (Feedback-System)
                    </div>
                    <div class="content">
                        <p><strong>Felder (Einladung):</strong> Name und E-Mail des Eingeladenen, Spieler-ID, Zielgruppen-Typ (Kind / Teenager / Teamer / Erziehungsberechtigte/r), Versand- und Ablaufzeitpunkt</p>
                        <p><strong>Felder (Antwort):</strong> IP-Adresse, Abgabezeitpunkt, Freitextantworten, Bewertungs- und Ja/Nein-Antworten</p>
                        <p><strong>Tabellen:</strong> <code>survey_links</code>, <code>survey_responses</code>, <code>survey_answers</code></p>
                        <p><strong>Zweck:</strong> Qualitätssicherung durch strukturiertes Teilnehmerfeedback.</p>
                        <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an Qualitätssicherung). Bei Kindern/Jugendlichen werden Umfrage-Links an die E-Mail-Adresse der Erziehungsberechtigten versandt.</p>
                        <p><strong>IP-Adressen:</strong> <code>survey_responses.ip_address</code> wird ausschließlich für Anti-Spam-Rate-Limiting gespeichert und nach <strong>90 Tagen automatisch gelöscht</strong> (DsgvoPrune). Zugriff auf Umfrageergebnisse haben Projektleitung und Administrator.</p>
                        <p class="mb-0"><strong>Freitexte Minderjähriger:</strong> Kinder (<code>participant_child</code>) können Freitextantworten eingeben, die 2 Jahre gespeichert und dann anonymisiert werden (DsgvoPrune). Aggregierte Bewertungen (Durchschnittswerte) bleiben dauerhaft für die Jugendförderungs-Dokumentation erhalten.</p>
                    </div>

                    <div class="title">
                        <i class="dropdown icon"></i>
                        Nutzerverwaltung und Systemadministration
                    </div>
                    <div class="content">
                        <p><strong>Felder (Nutzerverwaltung):</strong> Vollständige Nutzerdaten aller Konten, Rollenzuweisung, Aktivierungsstatus, Login-Zeitstempel</p>
                        <p><strong>Felder (Spieleradministration):</strong> Alle Spielerprofile, CSV-Export aller Spielerdaten, Verwaltung von Betreuer-Kind-Zuordnungen, Bearbeitung von Kindanschriften</p>
                        <p><strong>Tabellen:</strong> <code>users</code>, <code>roles</code>, <code>role_user</code> (Pivot), <code>player_user</code> (Pivot)</p>
                        <p><strong>Zweck:</strong> Systemadministration; Verwaltung von Zugriffsrechten; Betreuerzuordnung für Minderjährige.</p>
                        <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. c DSGVO (rechtliche Verpflichtung, Datenschutz-Compliance) sowie Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an Systemsicherheit).</p>
                        <p class="mb-0"><strong>Zugriff:</strong> <code>users.manage</code> und <code>portal.manage</code> sind ausschließlich der Rolle Administrator zugewiesen.</p>
                    </div>

                </div>

                {{-- Technische Schutzmaßnahmen --}}
                <h3 class="ui dividing header">Technische Schutzmaßnahmen (Art. 32 DSGVO)</h3>
                <div class="ui two column stackable grid">
                    <div class="column">
                        <p class="text-sm font-medium text-stone-600 mb-2">Implementiert</p>
                        <div class="ui list">
                            <div class="item"><i class="check circle green icon"></i> Passwörter gehasht (bcrypt)</div>
                            <div class="item"><i class="check circle green icon"></i> Soft-Delete für Nutzer und Spieler (Wiederherstellbarkeit)</div>
                            <div class="item"><i class="check circle green icon"></i> Anonymisierung nach Art. 17 DSGVO implementiert</div>
                            <div class="item"><i class="check circle green icon"></i> Datenexport nach Art. 20 DSGVO (JSON-Download)</div>
                            <div class="item"><i class="check circle green icon"></i> Öffentliche Helden-Profile: opt-in, kein Realname</div>
                            <div class="item"><i class="check circle green icon"></i> Einwilligungsnachweis (<code>health_data_consent_at</code>) in Buchungen, Spielerprofil und Teamer-Anmeldungen [H-1, H-2]</div>
                            <div class="item"><i class="check circle green icon"></i> E-Mail-Verifikation bei Kontoanlage</div>
                            <div class="item"><i class="check circle green icon"></i> Biometrische Unterschriften AES-256-verschlüsselt</div>
                            <div class="item"><i class="check circle green icon"></i> Automatisches Löschkonzept für Buchungs- und Teamer-Gesundheitsdaten (DsgvoPrune) [H-3]</div>
                            <div class="item"><i class="check circle green icon"></i> IP-Adressen in Umfragen nach 90 Tagen gelöscht (DsgvoPrune)</div>
                            <div class="item"><i class="check circle green icon"></i> Datenpannen-Protokoll mit 72h-Frist (Art. 33)</div>
                            <div class="item"><i class="check circle green icon"></i> Gesendete Mails im IMAP-Postfach nachvollziehbar</div>
                            <div class="item"><i class="check circle green icon"></i> Elterliche Einwilligung <code>parental_consent_at</code> in <code>players</code> (Art. 8 DSGVO) [H-5]</div>
                            <div class="item"><i class="check circle green icon"></i> Matrix-Dienst in Datenschutzerklärung als Drittempfänger aufgeführt [M-1]</div>
                            <div class="item"><i class="check circle green icon"></i> Teamer-Gesundheitsdaten in Datenschutzerklärung (Abschn. 2.4) dokumentiert [M-2]</div>
                            <div class="item"><i class="check circle green icon"></i> Newsletter: Double-Opt-in, Einwilligung Art. 6 Abs. 1 lit. a, Abmeldelink</div>
                        </div>
                    </div>
                    <div class="column">
                        <p class="text-sm font-medium text-stone-600 mb-2">Handlungsbedarf</p>
                        <div class="ui list">
                            <div class="item">
                                <i class="exclamation circle orange icon"></i>
                                <strong>[M-3]</strong> Kein Audit-Log für Buchungsänderungen und Spielerprofil-Edits durch Admins
                            </div>
                            <div class="item">
                                <i class="exclamation circle orange icon"></i>
                                <strong>[N-1]</strong> Kein Verarbeitungsverzeichnis nach Art. 30 DSGVO
                            </div>
                            <div class="item">
                                <i class="exclamation circle orange icon"></i>
                                <strong>[N-2]</strong> Fotoerlaubnis ohne dedizierten Widerrufsmechanismus (Art. 7 DSGVO)
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Hinweis für Datenschutzbeauftragten --}}
                <div class="ui message mt-4">
                    <div class="header">Hinweis für den Datenschutzbeauftragten</div>
                    <p>
                        Diese Übersicht basiert auf der technischen Analyse der Anwendung
                        (Stand: {{ now()->format('d.m.Y') }}, Prüfung durch automatisiertes Privacy-Review).
                        Sie ersetzt nicht das Verarbeitungsverzeichnis nach Art. 30 DSGVO (Mangel N-1), das gesondert zu führen ist.
                    </p>
                    <p>
                        <strong>Offene Punkte:</strong> M-3 (Audit-Log für Buchungsänderungen), N-1 (Verarbeitungsverzeichnis nach Art. 30)
                        und N-2 (Fotoerlaubnis-Widerruf) sind noch nicht implementiert.
                        Das <a href="{{ route('admin.data-breaches.index') }}" class="text-blue-700 underline">Datenpannen-Protokoll</a>
                        steht für die Dokumentation von Vorfällen bereit.
                    </p>
                    <p class="mb-0">
                        <strong>Betroffenenrechte:</strong>
                        Auskunft (Art. 15), Berichtigung (Art. 16) und Löschung (Art. 17) können über das Profil
                        oder per Anfrage an die Administration geltend gemacht werden. Ein automatisierter
                        Datenexport nach Art. 20 DSGVO ist unter <em>Profil &rsaquo; Daten exportieren</em> verfügbar.
                    </p>
                </div>

            </div>

            @can('portal.manage')
            <div>
                <a href="{{ route('admin.index') }}">
                    <x-primary-button>Zurück zur Verwaltung</x-primary-button>
                </a>
            </div>
            @endcan

        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.jQuery('.ui.accordion').accordion();
        });
    </script>
    @endpush

</x-app-layout>
