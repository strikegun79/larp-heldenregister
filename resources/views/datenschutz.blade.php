<x-public-layout title="Datenschutzerklärung – {{ config('portal.name') }}">
<div class="max-w-3xl mx-auto px-4 py-8 prose prose-stone">

    <h1 class="font-medieval text-3xl text-waldritter mb-2">Datenschutzerklärung</h1>
    <p class="text-sm text-stone-500 mb-8">Stand: {{ date('F Y') }} · {{ config('portal.organization') }}</p>

    <h2>1. Verantwortliche Stelle</h2>
    <p>
        Verantwortlich für die Verarbeitung personenbezogener Daten im Sinne der DSGVO ist:
    </p>
    <p>
        <strong>{{ config('portal.organization') }}</strong><br>
        E-Mail: <a href="mailto:{{ config('portal.email') }}">{{ config('portal.email') }}</a><br>
        Datenschutz-Kontakt: <a href="mailto:datenschutz@waldritter-giessen.de">datenschutz@waldritter-giessen.de</a>
    </p>

    <h2>2. Verarbeitete Daten und Zwecke</h2>

    <h3>2.1 Benutzerkonto (Erziehungsberechtigte / Betreuer)</h3>
    <p>
        Bei der Registrierung erheben wir: Vorname, Nachname, E-Mail-Adresse, Telefonnummer sowie
        Postanschrift. Diese Daten sind für die Teilnahme an Veranstaltungen und die Kommunikation
        zwischen Orga und Erziehungsberechtigten erforderlich.
    </p>
    <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung).</p>

    <h3>2.2 Spieler-Profile (Kinder und Jugendliche)</h3>
    <p>
        Für jeden Spieler werden gespeichert: Vorname, Nachname, Geburtsdatum, Geschlecht sowie
        optional eine E-Mail-Adresse und Postanschrift. Das Geburtsdatum wird zur Altersüberprüfung
        und zur Berechnung altersbezogener Werte im Spiel verwendet.
    </p>
    <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung) in Verbindung
    mit der Einwilligung des Erziehungsberechtigten (Art. 8 DSGVO für Personen unter 16 Jahren).</p>

    <h3>2.3 Veranstaltungsanmeldungen</h3>
    <p>
        Bei der Anmeldung zu einem Abenteuer werden folgende Daten erhoben:
        Teilnehmerrolle, Notfallkontakt, Erreichbarkeit, Foto- und Veröffentlichungserlaubnis
        sowie optionale Angaben zu Ernährungsweise und Ausrüstungsbedarf.
    </p>
    <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. b DSGVO.</p>

    <h3>2.4 Gesundheitsdaten (Allergien und Medikamente)</h3>
    <p>
        Im Rahmen der <strong>Veranstaltungsanmeldung</strong> und der <strong>Teamer-Anmeldung</strong>
        können auf freiwilliger Basis Allergien und Medikamente angegeben werden. Diese Angaben werden
        <strong>ausschließlich zum Schutz des Teilnehmers in Notfallsituationen</strong> an die
        Veranstaltungsleitung weitergegeben und nicht für andere Zwecke verwendet.
    </p>
    <p>
        Die Einwilligung zur Verarbeitung dieser besonderen Datenkategorie wird mit einem
        Einwilligungszeitstempel (<code>health_data_consent_at</code>) in der Datenbank dokumentiert
        (Art. 9 Abs. 2 lit. a DSGVO).
    </p>
    <p>
        <strong>Rechtsgrundlage:</strong> Art. 9 Abs. 2 lit. c DSGVO (Schutz lebenswichtiger
        Interessen) in Verbindung mit der ausdrücklichen Einwilligung nach Art. 9 Abs. 2 lit. a DSGVO,
        die separat erteilt werden muss.
    </p>

    <h3>2.5 Teilnahme-Unterschriften (Check-in)</h3>
    <p>
        Beim Veranstaltungs-Check-in wird eine digitale Unterschrift erfasst und der
        Anmeldung zugeordnet. Die Unterschrift ist verschlüsselt gespeichert und wird
        automatisch <strong>30 Tage nach Veranstaltungsende</strong> gelöscht.
    </p>
    <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. b DSGVO.</p>

    <h3>2.6 Protokollierung (Audit-Log)</h3>
    <p>
        Verwaltungsaktionen (z. B. Änderungen an Nutzerdaten) werden in einem internen
        Protokoll festgehalten. Diese Protokolle werden nach spätestens 3 Jahren gelöscht.
    </p>
    <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an
    Nachvollziehbarkeit von Änderungen).</p>

    <h3>2.7 Veranstaltungs-Feedback (Umfragen zur Jugendförderung)</h3>
    <p>
        Nach Veranstaltungen können Teilnehmer, Teamer und Erziehungsberechtigte freiwillig
        an einer Rückmeldebefragung teilnehmen. Die Einladung erfolgt per personalisiertem
        Link per E-Mail; ein Passwort ist nicht erforderlich.
    </p>
    <p>
        <strong>Erhoben werden:</strong> Bewertungen (Zahlenwerte 1–10), Ja/Nein-Antworten
        sowie optionale Freitextantworten. Freitexte sind personenbezogen auswertbar und
        werden daher <strong>ausschließlich von berechtigten Betreuer/innen</strong> eingesehen;
        sie werden nach spätestens 2 Jahren automatisch anonymisiert. Aggregierte Bewertungen
        (Durchschnittswerte) verbleiben dauerhaft für die Jugendförderungs-Dokumentation.
    </p>
    <p>
        Die Teilnahme an Umfragen ist freiwillig. Bei Kindern unter 16 Jahren werden
        Erziehungsberechtigte vorab über die Befragung informiert.
    </p>
    <p>
        <strong>Zweck:</strong> Qualitätssicherung und Dokumentation für die Jugendförderung
        gemäß den Anforderungen des SGB VIII (Kinder- und Jugendhilfe).
    </p>
    <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an
    Qualitätssicherung und Fördernachweisen) sowie Art. 6 Abs. 1 lit. a DSGVO (Einwilligung
    bei optionalen Freitextangaben).</p>

    <h3>2.8 Newsletter und Mitglieder-Kommunikation</h3>
    <p>
        Mitglieder können freiwillig einen Newsletter des Vereins abonnieren. Die Anmeldung
        erfolgt im Double-Opt-in-Verfahren: Nach der Registrierung wird eine Bestätigungs-E-Mail
        versandt; das Abonnement wird erst nach Bestätigung des enthaltenen Links aktiviert.
    </p>
    <p>
        <strong>Erhoben werden:</strong> E-Mail-Adresse, Zeitpunkt der Einwilligung und Bestätigung.
        Der Newsletter enthält einen Abmeldelink; die Abmeldung ist jederzeit auch über das eigene
        Profil möglich.
    </p>
    <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. a DSGVO (Einwilligung).</p>

    <h2>3. Datenweitergabe an Dritte</h2>
    <p>
        Wir geben Daten nur weiter, soweit dies gesetzlich erlaubt oder erforderlich ist:
    </p>
    <ul>
        <li><strong>E-Mail-Versand:</strong> Bestätigungs-, Benachrichtigungs- und Newsletter-E-Mails werden über
        den SMTP-Dienst unseres Hosters (netcup GmbH) versendet. Es besteht ein
        Auftragsverarbeitungsvertrag.</li>
        <li><strong>Datenbankhosting:</strong> Die Datenbank liegt auf einem Server in Deutschland
        (netcup GmbH, Karlsruhe).</li>
        <li><strong>Gruppen-Kommunikation (Matrix/Element):</strong> Für die interne Team-Kommunikation
        wird ein selbst betriebener Matrix-Server (Element-Chat) genutzt. Dabei werden Nutzernamen
        und Raum-Mitgliedschaften übertragen. Es besteht eine Auftragsverarbeitungsvereinbarung;
        der Server wird in Deutschland betrieben.</li>
        <li>Eine Weitergabe an sonstige Dritte findet nicht statt.</li>
    </ul>

    <h2>4. Speicherdauer</h2>
    <ul>
        <li>Benutzerkontodaten werden bis zur Löschung des Kontos gespeichert.</li>
        <li>Buchungsdaten vergangener Veranstaltungen werden <strong>3 Jahre</strong> nach Veranstaltungsende anonymisiert (personenbezogene Felder werden entfernt, statistische Daten bleiben erhalten).</li>
        <li>Gesundheitsdaten (Allergien, Medikation) in Veranstaltungs- und Teamer-Anmeldungen werden <strong>2 Jahre</strong> nach Veranstaltungsende gelöscht.</li>
        <li>Unterschriften werden <strong>30 Tage</strong> nach Veranstaltungsende gelöscht.</li>
        <li>Audit-Logs werden nach <strong>3 Jahren</strong> gelöscht.</li>
        <li>Umfrage-Freitextantworten werden nach <strong>2 Jahren</strong> anonymisiert; aggregierte Bewertungen (Durchschnittswerte) bleiben dauerhaft erhalten.</li>
        <li>IP-Adressen in Umfrage-Antworten werden nach <strong>90 Tagen</strong> gelöscht.</li>
        <li>Newsletter-Abonnements werden bei Abmeldung als abgemeldet markiert und nach Ablauf der gesetzlichen Aufbewahrungsfrist vollständig gelöscht.</li>
        <li>System-Benachrichtigungen werden nach <strong>1 Jahr</strong> gelöscht.</li>
    </ul>

    <h2>5. Rechte der betroffenen Personen</h2>
    <p>Du/Sie haben das Recht auf:</p>
    <ul>
        <li><strong>Auskunft</strong> über gespeicherte Daten (Art. 15 DSGVO)</li>
        <li><strong>Berichtigung</strong> unrichtiger Daten (Art. 16 DSGVO)</li>
        <li><strong>Löschung</strong> (Art. 17 DSGVO) – Konten können im Profil selbst gelöscht werden</li>
        <li><strong>Einschränkung der Verarbeitung</strong> (Art. 18 DSGVO)</li>
        <li><strong>Datenübertragbarkeit</strong> (Art. 20 DSGVO)</li>
        <li><strong>Widerspruch</strong> gegen die Verarbeitung (Art. 21 DSGVO)</li>
        <li><strong>Widerruf</strong> einer Einwilligung jederzeit, ohne Angabe von Gründen</li>
    </ul>
    <p>
        Zur Ausübung dieser Rechte wende dich an:
        <a href="mailto:datenschutz@waldritter-giessen.de">datenschutz@waldritter-giessen.de</a>
    </p>
    <p>
        Du hast außerdem das Recht, dich bei der zuständigen Datenschutz-Aufsichtsbehörde
        zu beschweren (Hessischer Beauftragter für Datenschutz und Informationsfreiheit,
        <a href="https://datenschutz.hessen.de" target="_blank" rel="noopener">datenschutz.hessen.de</a>).
    </p>

    <h2>6. Minderjährige</h2>
    <p>
        Unser Portal richtet sich an Erziehungsberechtigte von Kindern und Jugendlichen.
        Für die Verarbeitung personenbezogener Daten von Kindern unter 16 Jahren ist die
        Einwilligung der Erziehungsberechtigten erforderlich (Art. 8 DSGVO, § 8 BDSG).
        Durch die Registrierung und das Anlegen von Spielerprofilen bestätigen
        Erziehungsberechtigte, dass sie zur Einwilligung berechtigt sind.
    </p>

    <h2>7. Datensicherheit</h2>
    <p>
        Die Übertragung aller Daten erfolgt verschlüsselt per TLS (HTTPS). Passwörter werden
        ausschließlich als bcrypt-Hash gespeichert, nie im Klartext. Zugriffe sind durch ein
        Rollenberechtigungssystem abgesichert. Datenbankbackups werden verschlüsselt erstellt.
    </p>

    <h2>8. Änderungen dieser Erklärung</h2>
    <p>
        Wir behalten uns vor, diese Datenschutzerklärung bei Bedarf anzupassen.
        Die jeweils aktuelle Version ist unter
        <a href="{{ route('datenschutz') }}">{{ url('/datenschutz') }}</a> abrufbar.
    </p>

</div>
</x-public-layout>
