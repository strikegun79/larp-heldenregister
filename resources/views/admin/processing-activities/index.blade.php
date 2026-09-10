<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h2 class="font-uncial text-2xl text-waldritter leading-tight">
                Verarbeitungsverzeichnis (Art. 30 DSGVO)
            </h2>
            <button onclick="window.print()" class="ui button">
                <i class="print icon"></i> Drucken / PDF
            </button>
        </div>
    </x-slot>

    <style>
        @media print {
            nav, header, .no-print { display: none !important; }
            .vvt-section { page-break-inside: avoid; }
            body { font-size: 11pt; }
        }
    </style>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Kopfdaten --}}
            <div class="bg-white/70 border-2 border-[#5a3a22]/40 shadow rounded-lg p-5 text-sm">
                <h3 class="font-uncial text-lg text-waldritter mb-3">Verantwortlicher</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-stone-700">
                    <div>
                        <dt class="text-stone-500 text-xs">Organisation</dt>
                        <dd class="font-medium">{{ config('portal.name', 'Waldritter Gießen e.V.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500 text-xs">Kontakt</dt>
                        <dd>{{ \App\Models\Setting::get('contact_email', '—') }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500 text-xs">Stand</dt>
                        <dd>{{ $generatedAt->format('d.m.Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500 text-xs">Rechtsgrundlage des Verzeichnisses</dt>
                        <dd>Art. 30 Abs. 1 DSGVO</dd>
                    </div>
                </dl>
                <p class="mt-3 text-xs text-stone-500">
                    Dieses Verzeichnis dokumentiert alle wesentlichen Verarbeitungstätigkeiten des Heldenregisters.
                    Es ist auf Anfrage der zuständigen Aufsichtsbehörde vorzulegen (Art. 30 Abs. 4 DSGVO).
                    Bei Änderungen der Verarbeitungstätigkeiten ist es zu aktualisieren.
                </p>
            </div>

            @php
            $activities = [
                [
                    'nr'         => 1,
                    'name'       => 'Nutzerverwaltung (Portal-Konten)',
                    'zweck'      => 'Bereitstellung des Portal-Zugangs, Authentifizierung, Rollenzuweisung und Rechteverwaltung für Erziehungsberechtigte und Vereinsmitglieder.',
                    'grundlage'  => 'Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung); Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an der Sicherheit des Portals).',
                    'personen'   => 'Erziehungsberechtigte, Betreuer, Vereinsmitglieder, ehrenamtliche Helfer (Teamer, Projektleitung, Bürokrat).',
                    'daten'      => 'Vorname, Nachname, E-Mail-Adresse, Passwort (bcrypt-gehashed), Telefonnummer, Postanschrift, Vereinsrolle(n), Benachrichtigungseinstellungen, Zeitstempel (Erstellt, Verifiziert, Gelöscht).',
                    'intern'     => 'Admin (vollständig), Bürokrat (lesen), eigener Nutzer (eigene Daten).',
                    'extern'     => 'Keine. E-Mail-Versand über konfigurierten SMTP-Dienst (Auftragsverarbeiter).',
                    'drittland'  => 'Nein (abhängig vom SMTP-Anbieter – ggf. AV-Vertrag erforderlich).',
                    'loeschung'  => 'Soft-Delete bei Kontoauflösung; vollständige Löschung auf Antrag (Art. 17 DSGVO). Passwort-Hashes: sofort bei Löschung.',
                    'tom'        => 'bcrypt-Passwort-Hashing, E-Mail-Verifikation, HTTPS, rollenbasierte Zugriffskontrolle.',
                ],
                [
                    'nr'         => 2,
                    'name'       => 'Spieler-/Teilnehmerverwaltung',
                    'zweck'      => 'Verwaltung der LARP-Teilnehmer (überwiegend Kinder und Jugendliche) einschließlich Stammdaten, Kontakt und Avatar.',
                    'grundlage'  => 'Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung); Art. 8 DSGVO i. V. m. § 8 BDSG (elterliche Einwilligung für Minderjährige, dokumentiert als parental_consent_at).',
                    'personen'   => 'Kinder und Jugendliche (LARP-Teilnehmer); vereinzelt Erwachsene.',
                    'daten'      => 'Vorname, Nachname, Geburtsdatum, Geschlecht, E-Mail (optional), Postanschrift (optional, abweichend von Erziehungsberechtigtem), Profilfoto (Avatar), Aktivstatus, Zeitstempel.',
                    'intern'     => 'Admin (vollständig), Bürokrat (lesen/bearbeiten), zugeordnete Erziehungsberechtigte (eigene Spieler).',
                    'extern'     => 'Keine.',
                    'drittland'  => 'Nein.',
                    'loeschung'  => 'Soft-Delete auf Antrag; Anonymisierung (Art. 17 DSGVO) über Funktion anonymize() möglich. Profilfoto: sofort bei Löschung/Anonymisierung.',
                    'tom'        => 'HTTPS, rollenbasierte Zugriffskontrolle, Soft-Delete mit Wiederherstellungsoption, Anonymisierungsfunktion.',
                ],
                [
                    'nr'         => 3,
                    'name'       => 'Veranstaltungsanmeldungen (Buchungen)',
                    'zweck'      => 'Anmeldung und Verwaltung der Teilnahme an LARP-Veranstaltungen; Sicherstellung des Kinderschutzes (Notfallkontakt, Gesundheitsangaben); Abwicklung der Teilnahmebeiträge.',
                    'grundlage'  => 'Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung); für Gesundheitsdaten: Art. 9 Abs. 2 lit. a DSGVO (ausdrückliche Einwilligung, dokumentiert als health_data_consent_at); für Unterschrift (biometrisch): Art. 9 Abs. 2 lit. a DSGVO.',
                    'personen'   => 'LARP-Teilnehmer (Kinder/Jugendliche) und anmeldende Erziehungsberechtigte/Betreuer.',
                    'daten'      => 'Angemeldeter Spieler, Abenteuer, Ereignisrolle, Fotoerlaubnis, Vegetarier-Kennzeichen, Leihausstattung (Tunika/Waffe), NSC-Kennzeichen, Allergien und Unverträglichkeiten (Art. 9), Medikamente (Art. 9), Erreichbarkeit, Notfall-Telefonnummer, Ermäßigungsstatus, Bezahlstatus, Wartelistenstatus, Anmeldestatus, digitale Unterschrift (AES-256-verschlüsselt, Art. 9).',
                    'intern'     => 'Projektleitung und Bürokrat (vollständig), Teamer/Lehrmeister (lesen, ohne Gesundheitsdaten), anmeldende Person (eigene Buchung).',
                    'extern'     => 'Keine. Zahlungsabwicklung manuell (SEPA-Überweisung, keine externen Zahlungsdienstleister).',
                    'drittland'  => 'Nein.',
                    'loeschung'  => 'Gesundheitsdaten (Allergien, Medikamente, health_data_consent_at) und digitale Unterschrift: automatisch 30 Tage nach Veranstaltungsende (DsgvoPrune-Befehl). Buchungsdatensatz selbst: bei Stornierung (Soft-Delete per Status); vollständige Löschung auf Antrag.',
                    'tom'        => 'AES-256-Verschlüsselung der Unterschrift (encrypted-Cast), HTTPS, rollenbasierte Zugriffskontrolle, automatische Datenlöschung (DsgvoPrune), Audit-Log für Admin-Änderungen (M-3).',
                ],
                [
                    'nr'         => 4,
                    'name'       => 'Veranstaltungsdurchführung (Teilnahmedokumentation)',
                    'zweck'      => 'Dokumentation der tatsächlichen Teilnahme an Veranstaltungen als Grundlage für die Erfahrungspunkte-Vergabe und für Fördernachweise des Vereins.',
                    'grundlage'  => 'Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung – EP-Vergabe); Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an Vereinsdokumentation/Fördernachweisen).',
                    'personen'   => 'LARP-Teilnehmer.',
                    'daten'      => 'Spieler-ID, Abenteuer-ID, Held-ID, Zeitstempel der Erfassung (event_visits-Tabelle).',
                    'intern'     => 'Projektleitung, Bürokrat, Teamer/Lehrmeister (Erfassung); Admin (vollständig).',
                    'extern'     => 'Keine (ggf. anonymisierte Aggregatdaten für Vereinsberichte).',
                    'drittland'  => 'Nein.',
                    'loeschung'  => 'Keine automatische Löschung (Fördernachweispflicht des Vereins). Löschung auf Antrag möglich (Art. 17 DSGVO), sofern keine Aufbewahrungspflicht entgegensteht.',
                    'tom'        => 'HTTPS, rollenbasierte Zugriffskontrolle.',
                ],
                [
                    'nr'         => 5,
                    'name'       => 'Heldenverwaltung (LARP-Charaktere)',
                    'zweck'      => 'Dokumentation und Fortschritt der LARP-Charaktere (Helden) der Teilnehmer; Verwaltung von Fertigkeiten und Erfahrungspunkten.',
                    'grundlage'  => 'Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung – Spielvertrag).',
                    'personen'   => 'LARP-Teilnehmer (als Charaktere pseudonymisiert).',
                    'daten'      => 'Charaktername (Pseudonym, kein Realname), Klasse(n), Fertigkeiten, Erfahrungspunkte und Transaktionen, Charakterbild (optional), Aktiv-Status, Verschollen-Status.',
                    'intern'     => 'Eigener Nutzer (eigener Held), Teamer/Lehrmeister/Projektleitung (lesen), Bürokrat/Admin (vollständig).',
                    'extern'     => 'Öffentliches Heldenarchiv (nur freigegebene Helden, ohne Realdaten).',
                    'drittland'  => 'Nein.',
                    'loeschung'  => 'Helden bleiben bei Spieler-Löschung erhalten (Charaktername ist kein Realname). Löschung auf Antrag möglich. Charakterbild: sofort bei Löschung.',
                    'tom'        => 'HTTPS, rollenbasierte Zugriffskontrolle, Trennung Realname/Charaktername.',
                ],
                [
                    'nr'         => 6,
                    'name'       => 'Newsletter und Vereinskommunikation',
                    'zweck'      => 'Versand von Informationen zu Veranstaltungen, Neuigkeiten und Vereinsnachrichten an interessierte Personen.',
                    'grundlage'  => 'Art. 6 Abs. 1 lit. a DSGVO (Einwilligung); Double-Opt-in-Verfahren.',
                    'personen'   => 'Newsletter-Abonnenten (Eltern, Mitglieder, Interessierte).',
                    'daten'      => 'E-Mail-Adresse, Einwilligungszeitpunkt (Double-Opt-in), Bestätigungstoken, Abmeldestatus.',
                    'intern'     => 'Admin (vollständig), Bürokrat (lesen/Versand).',
                    'extern'     => 'E-Mail-Versand über konfigurierten SMTP-Dienst (Auftragsverarbeiter, AV-Vertrag empfohlen).',
                    'drittland'  => 'Nein (abhängig vom SMTP-Anbieter).',
                    'loeschung'  => 'Bei Abmeldung: sofortige Löschung der E-Mail-Adresse.',
                    'tom'        => 'Double-Opt-in, Abmeldelink in jeder Mail, HTTPS.',
                ],
                [
                    'nr'         => 7,
                    'name'       => 'Veranstaltungsumfragen (Feedback)',
                    'zweck'      => 'Qualitätssicherung und Verbesserung von Veranstaltungen durch anonymisierte Teilnehmerbefragungen.',
                    'grundlage'  => 'Art. 6 Abs. 1 lit. a DSGVO (Einwilligung bei freiwilliger Teilnahme); Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an Qualitätssicherung).',
                    'personen'   => 'Veranstaltungsteilnehmer (Erziehungsberechtigte und Spieler).',
                    'daten'      => 'Umfrageantworten (freitext und strukturiert), IP-Adresse (temporär), Zeitstempel, Verknüpfung zu Buchung/Spieler (pseudonymisiert über Einladungslink).',
                    'intern'     => 'Projektleitung, Bürokrat, Admin (Ergebnisse); Teamer mit survey.view (aggregierte Ansicht).',
                    'extern'     => 'Keine.',
                    'drittland'  => 'Nein.',
                    'loeschung'  => 'IP-Adressen: automatisch nach 90 Tagen (DsgvoPrune). Umfrageantworten: auf Antrag.',
                    'tom'        => 'HTTPS, IP-Anonymisierung nach 90 Tagen, Einladungslinks mit Token (kein direkter Nutzer-Login nötig).',
                ],
                [
                    'nr'         => 8,
                    'name'       => 'Matrix-Kommunikationsdienst (Teamer-Koordination)',
                    'zweck'      => 'Bereitstellung von Kommunikationskanälen für ehrenamtliche Helfer (Teamer, Projektleitung) zur Veranstaltungskoordination.',
                    'grundlage'  => 'Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an effizienter Vereinsorganisation).',
                    'personen'   => 'Teamer, Lehrmeister, Projektleitung.',
                    'daten'      => 'Matrix-Benutzer-ID (abgeleitet aus Realname: @vorname.nachname:domain), Raumzugehörigkeit.',
                    'intern'     => 'Admin (Kontoverwaltung).',
                    'extern'     => 'Matrix-Server-Betreiber als Auftragsverarbeiter (AV-Vertrag erforderlich). Matrix ist ein föderiertes Protokoll – bei Raumteilnahme externer Nutzer ggf. Datenweitergabe an externe Server.',
                    'drittland'  => 'Abhängig vom Matrix-Server-Betreiber. Sofern Server in EU/EWR: nein.',
                    'loeschung'  => 'Bei Rollenentzug: Matrix-Konto-Deaktivierung. Vollständige Löschung beim Server-Betreiber separat zu veranlassen.',
                    'tom'        => 'HTTPS/TLS, Matrix-Ende-zu-Ende-Verschlüsselung in verschlüsselten Räumen, AV-Vertrag mit Server-Betreiber empfohlen.',
                ],
                [
                    'nr'         => 9,
                    'name'       => 'Administratives Protokoll (Audit-Log)',
                    'zweck'      => 'Nachvollziehbarkeit administrativer Aktionen (Rollenänderungen, Buchungseingriffe, Spieler-Edits) zur Sicherstellung der Rechenschaftspflicht (Art. 5 Abs. 2 DSGVO).',
                    'grundlage'  => 'Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an Sicherheit und Nachvollziehbarkeit).',
                    'personen'   => 'Portal-Nutzer (als handelnde Akteure), betroffene Spieler/Buchungen (als Subjekt).',
                    'daten'      => 'Aktor-ID, Aktorname, Aktionsbezeichnung, Betreff-Typ/-ID/-Label, geänderte Felder (JSON), Zeitstempel.',
                    'intern'     => 'Admin (vollständig), Bürokrat (lesen).',
                    'extern'     => 'Keine.',
                    'drittland'  => 'Nein.',
                    'loeschung'  => 'Keine automatische Löschung (Rechenschaftspflicht). Auf Antrag nach Einzelfallprüfung.',
                    'tom'        => 'HTTPS, rollenbasierte Zugriffskontrolle (nur Admin/Bürokrat), keine Löschfunktion im UI.',
                ],
            ];
            @endphp

            {{-- Einträge --}}
            @foreach ($activities as $a)
            <div class="vvt-section bg-white/70 border-2 border-[#5a3a22]/40 shadow rounded-lg overflow-hidden">
                <div class="bg-[#5a3a22]/10 px-5 py-3 flex items-center gap-3 border-b border-[#5a3a22]/20">
                    <span class="font-uncial text-waldritter text-sm shrink-0">{{ $a['nr'] }}.</span>
                    <h3 class="font-semibold text-stone-800">{{ $a['name'] }}</h3>
                </div>
                <div class="p-5">
                    <dl class="grid grid-cols-1 gap-3 text-sm">
                        <div>
                            <dt class="text-xs text-stone-500 font-medium mb-0.5">Zweck der Verarbeitung</dt>
                            <dd class="text-stone-800">{{ $a['zweck'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-stone-500 font-medium mb-0.5">Rechtsgrundlage</dt>
                            <dd class="text-stone-800">{{ $a['grundlage'] }}</dd>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <dt class="text-xs text-stone-500 font-medium mb-0.5">Kategorien betroffener Personen</dt>
                                <dd class="text-stone-800">{{ $a['personen'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-stone-500 font-medium mb-0.5">Kategorien personenbezogener Daten</dt>
                                <dd class="text-stone-800">{{ $a['daten'] }}</dd>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <dt class="text-xs text-stone-500 font-medium mb-0.5">Interne Zugriffsberechtigte</dt>
                                <dd class="text-stone-800">{{ $a['intern'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-stone-500 font-medium mb-0.5">Externe Empfänger / Auftragsverarbeiter</dt>
                                <dd class="text-stone-800">{{ $a['extern'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-stone-500 font-medium mb-0.5">Drittlandübermittlung</dt>
                                <dd class="text-stone-800">{{ $a['drittland'] }}</dd>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <dt class="text-xs text-stone-500 font-medium mb-0.5">Lösch- / Aufbewahrungsfristen</dt>
                                <dd class="text-stone-800">{{ $a['loeschung'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-stone-500 font-medium mb-0.5">Technisch-organisatorische Maßnahmen (TOMs)</dt>
                                <dd class="text-stone-800">{{ $a['tom'] }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </div>
            @endforeach

            {{-- Fußnote --}}
            <div class="text-xs text-stone-500 pb-6 no-print">
                Dieses Verzeichnis wurde automatisch auf Basis der implementierten Systemkomponenten erstellt.
                Es ist kein Ersatz für rechtliche Beratung. Bei Änderungen der Verarbeitungstätigkeiten
                ist es zu aktualisieren. Stand: {{ $generatedAt->format('d.m.Y') }}.
            </div>

        </div>
    </div>
</x-app-layout>
