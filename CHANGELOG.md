# Versionsverlauf

Alle wesentlichen Änderungen am Heldenregister werden hier festgehalten.
Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionsnummern folgen [Semantic Versioning](https://semver.org/lang/de/).

## [1.9.0] – 2026-11-25

### Neu
- **Nextcloud Talk:** Das Portal ist an Nextcloud Talk angebunden. Admins richten die Verbindung in der Verwaltung ein. Je Event lassen sich ein Plot-Thread („Plot: …“) und ein Teamer-Raum anlegen, bestätigte Teamer werden automatisch abgeglichen. Spielende erhalten mit Einverständnis der Erziehungsberechtigten Zugang. Dazu kommen Regeln für die E-Mail-Adressen der Talk-Konten, Benachrichtigungen, ein Talk-Protokoll und verständlichere Fehlermeldungen. Im Teamer-Reiter stehen Links zu Raum und Thread.
- **Plot-Charaktere:** Verzeichnis bekannter Plot-Charaktere mit ausführlichen Profilen und der neuen Rolle „Plotschreiber“. Die bekannten Charaktere Silvaniens sind bereits angelegt. Das Heldenverzeichnis schlägt zufällig Plot-Charaktere vor.
- **Öffentliches Heldenverzeichnis:** Schlägt Helden vor, deren Besitzer der Veröffentlichung zugestimmt haben.
- **Teamer-Aufwandsentschädigung:** Abrechenbare Event-Rollen und Entschädigung je Abenteuer. Teamer hinterlegen Steuernummer und Bankdaten im Profil und laden den ausgefüllten Antrag als PDF herunter; Startseite und Abenteuer-Detail weisen darauf hin. Die Verwaltung liegt im Reiter „Teamer/NSC“, der PDF-Dateiname enthält das Startdatum des Events.
- **Umfragen:** Neue Fragetypen „Optionen mit Freitext“ und „Smileys 1–5“. Vorlagen lassen sich sortieren und in Abschnitte bzw. Abschnittsseiten gliedern, Smiley-Fragen erhalten eine Zusatzoption. Umfragen und Vorlagen lassen sich in einer Vorschau ansehen, ohne dass Antworten gespeichert werden.
- **EP-Standardwerte** je EP-Buchungsart.
- **Heldeneid** je Klasse im Heldenbuch für Bürokraten.
- **Perlenfarbe** im Fertigkeiten-Fenster.
- **Vegetarisches Essen:** Neue Event-Einstellung zur Abfrage bei der Anmeldung; Gastanmeldungen mit Leih- und NSC-Optionen.
- **Letzter Zahlungsabgleich** wird je Abenteuer angezeigt.
- **Benutzerliste der Verwaltung:** Filter nach Rolle, Live-Suche und Ein-/Ausblenden gelöschter Konten.

### Geändert
- **Neues Design „Waldritter“:** Startseite, Navigation (waldgrün und barrierearm), Abenteuerliste und -details, Heldenbuch, Spielerseiten, Formulare und Verwaltung wurden einheitlich neu gestaltet.
- **Verwaltung:** Einträge in den Listen und „Neu …“ öffnen sich im Inspektor, Löschen liegt im Gefahrenbereich des Inspektors.
- **Startseite:** Banner für das gebuchte Abenteuer mit vollständigen Daten, ausblendbare Einführung und Fortschrittsanzeige der Helden.
- **Spielende löschen:** Ihre Helden werden ausgeblendet und nach 30 Tagen anonymisiert.
- **Reiter „Talk“** in der Event-Verwaltung wird beim Öffnen frisch geladen, sodass gerade bestätigte Teamer und neue Protokolleinträge sofort sichtbar sind.
- Öffentliche Helden- und Plot-Charakterseiten sowie das Heldenverzeichnis nutzen auf großen Bildschirmen mehr Breite.

### Behoben
- Talk-Links funktionieren auch ohne „schöne“ URLs.
- Die Hauptnavigation erscheint auch auf öffentlichen Seiten für angemeldete Nutzer.
- Umfragevorlagen bleiben bearbeitbar, wenn bereits Antworten vorliegen.
- Die zugewiesene Teamer-Rolle wird im Abenteuer-Detail und in der Teilnehmer-PDF angezeigt.
- Beim Bearbeiten eines Kindes ist „Mein Kind“ wieder vorausgewählt.
- Die Heldengalerie aktualisiert sich nach dem Hochladen.
- Fehlende Vollseiten für Fertigkeiten ergänzt.

## [1.8.0] – 2026-11-17

### Neu
- **Einverständniserklärungen je Auftraggeber:** In der Verwaltung lassen sich für einen Auftraggeber zusätzliche Erklärungen hinterlegen, z. B. die Einverständniserklärung der Jugendförderung Gießen. Der Text ist formatierbar, eine PDF kann optional angehängt werden. Bei der Anmeldung zu Events dieses Auftraggebers muss jede Erklärung wie die Teilnahmebedingungen bestätigt werden. Der Text öffnet sich per Klick im Fenster, die PDF lässt sich über das PDF-Symbol herunterladen. Die Zustimmung wird mit Zeitpunkt gespeichert und im Inspektor angezeigt.
- **Inspektor in der Event-Verwaltung:** Ein Klick auf eine Anmeldung öffnet rechts alle wichtigen Daten und die Status-Aktionen (Warteliste, Ablehnen, Stornieren, Bestätigung erneut senden). Gilt auch für Teamer- und NSC-Anmeldungen und ist per Tastatur bedienbar.
- **Anmeldungen bestätigen:** Neue Event-Einstellung. Ist sie aktiv, bleiben Anmeldungen „offen“, bis die Projektleitung sie bestätigt. Die Eingangsmail enthält dann noch keine Bankdaten, die Zahlungsinformationen folgen mit der Bestätigung.
- **Mehrfach-Check-in:** Mehrere Teilnehmende auswählen und mit einer einzigen Unterschrift einchecken oder gemeinsam abmelden.
- **Check-in-Liste:** Durchsuchbar (Name, Held, Erziehungsberechtigte) und nach Spalten sortierbar.
- **Abmeldung zurücknehmen** im Check-in und im Inspektor.

### Geändert
- **Admin-Anmeldung:** Unabhängig vom Anmeldestatus möglich (außer bei abgesagten Abenteuern), immer bestätigt. Die Anmeldung erfolgt im Namen eines auswählbaren Betreuers, der auch die Bestätigungsmail erhält.
- **Verwaltungsbereich:** Karten und Fenster der Verwaltung sind mit einem roten Rahmen gekennzeichnet, die Reiter wurden überarbeitet.

### Behoben
- **Datumsfelder** lassen sich wieder per Tastatur eingeben (z. B. 15.03.2012).
- **Newsletter-Banner** auf der Startseite ist besser lesbar.

### Sicherheit
- „Auf Warteliste verschieben“ ist nur noch mit passender Berechtigung möglich.

## [1.7.0] – 2026-11-13

### Neu
- **Helden im Abenteuer:** Neuer Reiter „Helden“ in der Abenteuer-Ansicht für Lehrmeister, Projektleitung, Spielleitung, Bürokraten und Admins. Zeigt die aktiven Helden aller angemeldeten Teilnehmenden mit ihren Klassen und der Klassenverteilung – als Hilfe für die Plotplanung. Ein Klick öffnet die Heldenseite.

### Behoben
- **Teilnehmer-PDF:** Wer eingecheckt ist, aber keine Unterschrift hat, erhält statt eines leeren Feldes den Hinweis „keine Unterschrift - aber eingecheckt“.

## [1.6.0] – 2026-11-10

### Neu
- **Versionsverlauf:** Diese Seite – alle Änderungen am Portal auf einen Blick, verlinkt im Seitenfuß.
- **Brans Tagebuch:** Neue Kachel auf der Startseite mit Erzählungen aus den Abenteuern.

## [1.5.0] – 2026-10-26

### Neu
- **Startseiten-Nachrichten:** Die Orga kann Nachrichten mit Banner-Bild, Link, Farbe und Zeitraum auf der Startseite anzeigen; Nachrichten lassen sich wegklicken. Verwaltung mit Verlauf, Duplizieren und anonymem Klickzähler.
- **Nächstes Abenteuer auf der Startseite:** Anzeige der freien Plätze (grün/gelb/rot) und des Anmeldestatus (offen, Warteliste, geschlossen).
- **Steckbrief:** Formatierter Text mit Editor (Schriftgröße, Farben, Listen), eigene Ansicht im Fenster, Anzeige auch im öffentlichen Profil und im PDF.
- **Heldenausweis zum Herunterladen:** Besitzer können den Ausweis ihres Helden als PDF laden – mit Name und angekreuzten Klassen.
- **Klassenspezifische Voraussetzungen:** Fertigkeiten können je Klasse unterschiedliche Voraussetzungen haben.
- **Öffentlicher Skilltree:** Fertigkeiten lassen sich per Klick im Info-Fenster ansehen.

### Geändert
- **Teilnehmer-PDF:** Querformat, mehr Platz für Unterschriften, Rolle mit Zahlungsstatus, stornierte Anmeldungen ausgeblendet, Seitenzahlen.
- **Check-in:** Kein Neuladen der Seite mehr, Sortierung nach Nachname, neue Spalte „Held“.
- **EP-Vergabe:** Bestätigungsdialog mit Übersicht; EP werden nur noch über die Heldenansicht gebucht.
- **Newsletter:** Versand wird gedrosselt, damit alle Mails zuverlässig ankommen.
- **Offline-Seite:** Weist auf eine mögliche Serverwartung hin.

### Behoben
- Telefonnummern im Belegungsreport (Excel) werden wieder korrekt als Text exportiert.
- Freie Plätze in der Abenteuer-Übersicht werden korrekt berechnet.

## [1.4.0] – 2026-09-30

### Neu
- **Newsletter:** Abonnieren im Profil, Newsletter-Erstellung mit Editor, Bildern und Tabellen, Versand an alle Abonnenten.
- **Feedback-Umfragen:** Umfragesystem für Teilnehmende, z. B. für die Jugendförderung.
- **Taskmanager:** Automatische Aufgaben je Abenteuer (Erinnerungsmails, Statuswechsel, EP-Vergabe, Wartelisten, Umfragen).
- **Teamer-Rollen:** Teilnahme-Rollen aus der Verwaltung, vollständiger Teamer-Ablauf und Verwaltungs-Dashboard für Projekt- und Spielleitung.
- **Stornierung:** Anmeldungen werden storniert statt gelöscht; Rücknahme einer Stornierung kann angefragt werden.
- **Teilnahmebedingungen:** Eigene Seite für die AGB.
- **Datensparmodus:** Weniger Bilder und Datenverbrauch auf Wunsch.
- **E-Mail an alle Teilnehmer** direkt aus der Abenteuer-Verwaltung.
- Anmeldebestätigungen können erneut versendet werden.

### Datenschutz
- Ausdrückliche Einwilligungen für Gesundheitsdaten und für Kinderprofile.
- Fotoerlaubnis je Anmeldung widerrufbar, mit Benachrichtigung der Orga.
- Verarbeitungsverzeichnis, Datenpannen-Protokoll und Audit-Log für Änderungen.
- Automatische Löschung abgelaufener Daten und von Teamer-Gesundheitsdaten.
- Datenexport nach Art. 20 DSGVO verbessert.

### Geändert
- Admin-Tabellen und Suchfelder sind auf dem Smartphone besser bedienbar.
- Anmeldungstabelle in der Verwaltung ist sortierbar.
- Wartelistenmodus je Abenteuer umschaltbar, Altersgrenzen für Abenteuer.

## [1.3.0] – 2026-08-27

### Neu
- **Warteliste:** Anmeldungen landen bei vollen Abenteuern auf der Warteliste, mit Benachrichtigung beim Nachrücken.
- **Bezahlung:** GiroCode-QR in der Anmeldebestätigung, Ermäßigungspreise und Verwendungszweck mit Projektkürzel.
- **Onboarding:** Fortschrittsbox für neue Nutzer auf der Startseite.
- **Gesundheitsdaten im Spielerprofil** mit Vorausfüllung bei der Anmeldung.
- **Willkommens-Mail** für neue Helden; Anmeldung durch die Orga möglich.
- Belegungsreport als Excel-Datei statt CSV.
- Google-Maps-Link im Abenteuer, wenn ein Ort hinterlegt ist.
- E-Mails im Waldritter-Design mit Zahlungsinformationen.

### Datenschutz
- Datenschutzerklärung, lokale Schriften, Einwilligungen und Kontolöschung.
- Datenexport und automatische Aufbewahrungsfristen.

### Geändert
- Abenteuer-Übersicht mit Gruppenansicht und Sortierung.
- Detailansichten von Helden aktualisieren sich nach EP- oder Fertigkeitsänderungen automatisch.

## [1.2.0] – 2026-07-07

### Neu
- **Benachrichtigungen:** Portal- und E-Mail-Benachrichtigungen bei Statusänderungen, einstellbar im Profil.
- **Helden-Galerie:** Bis zu vier Bilder je Held.
- **Heldensuche per Siegel** im Heldenregister.
- **Fertigkeiten:** Eigene Symbole und Anzeige, welche Helden eine Fertigkeit beherrschen.
- **Gruppen-Anmeldung** für Abenteuer.
- **Hilfe-Seite** mit Funktionsübersicht für Vorstand und Auftraggeber.
- Klassenbänder mit Farbe und Bild.

### Geändert
- Abenteuer können gelöscht werden, solange keine Abhängigkeiten bestehen.
- Abgesagte und abgeschlossene Abenteuer werden für Spieler ausgeblendet.

## [1.1.0] – 2026-06-30

### Neu
- **Öffentliches Heldenprofil:** Jeder Held erhält einen Code, ein öffentliches Profil mit QR-Code und kann gesucht werden – auf Wunsch unsichtbar.
- **Helden-Siegel / Ausweis:** Ausweise als PDF im Klappformat.
- **Gruppen:** Gruppen mit Mitgliedern, sichtbar im Helden-Detail.
- **App-Installation (PWA):** Das Portal lässt sich auf dem Smartphone wie eine App installieren.
- Fertigkeitsbaum mit Spaltenansicht nach Stufe; Voraussetzungen werden beim Lernen geprüft.

### Geändert
- **Mobile Ansicht:** Untere Navigationsleiste, Karten statt breiter Tabellen, Akkordeons statt Tabs, mobiles Dashboard.
- Helden- und Abenteuer-Details als eigene Seiten statt Fenster.
- Barrierefreiheit verbessert (Tastaturbedienung, Fokus, Screenreader).

### Technik
- Automatische Backups, Logging, Hintergrundverarbeitung für E-Mails, automatische Tests.

## [1.0.0] – 2026-06-18

### Neu
- **Umstieg auf Laravel:** Das bisherige Portal wurde vollständig auf eine neue technische Grundlage gestellt. Bestehende Daten und Funktionen wurden übernommen.
- **Benutzerverwaltung:** Registrierung mit Pflichtangaben, E-Mail-Bestätigung, Rollen und Rechte, Konto-Deaktivierung.
- **Helden und Spieler:** Verwaltung von Spielern und Helden, EP-Buchungen, Perlen- und Bändchenübersicht.
- **Fertigkeiten:** Fertigkeitenkatalog und -verwaltung je Klasse.
- **Abenteuer:** Anmeldung, Teamer-Anmeldung und -Einladung, Teilnehmerliste als PDF.
- **Administration:** Verwaltung von Gruppen, Status, Perlenfarben, Buchungsarten und Portal-Einstellungen; Audit-Log.
